<?php


namespace Drupal\neptune_sync\Data;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\neptune_sync\Data\Model\CharacterSheet;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\Entity\Node;

class NeptuneImporter
{

    /**
     *  -wipe nodes
     *  -ITR types
     *      -query all types (change query to work on id)
     *      -get id's into php
     *      -check if id exist in drupal
     *      -if not add it
     *      -sync the node to ontology if body
     *
     */

    protected $entity_Mgr;
    protected $query_Mgr;


     /**
     * NeptuneImporter constructor.
     */
    public function __construct(){
        $this->entity_Mgr = new EntityManager();
        $this->query_Mgr = new QueryManager();
    }


    public function wipeNodes(){
        Helper::log("Deleting all Neptune data...",true);
        $nodes = $this->entity_Mgr->getAllNeptunetypes();
        foreach ($nodes as $node)
            try {
                $node->delete();
            } catch (EntityStorageException $e) {
                Helper::log("Err508 - Error deleting nodes during mass delete. Exiting. Error: " . $e);
                return false;
            }
        Helper::log("Finished deleting all neptune data", true);
        return true;
    }

    public function importBodies(){

        $c_mgr = new CharacterSheetManager();

        $query = QueryTemplate::getBodies();
        $json = $this->query_Mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);
        $drupalBodies = $this->entity_Mgr->getAllNodeType(SophiaGlobal::BODIES);

        $doesExist = false;
        foreach ($jsonObj->{'results'}->{'bindings'} as $obj) {
            foreach ($drupalBodies as $drupalBody)
                if ($obj->{'body'}->{'value'} ==
                    $drupalBody->get("field_neptune_uri")->getString()) {

                    $doesExist = true;
                    $c_mgr->updateCharacterSheet($drupalBody); //if it already exist, sync it
                }

            //if the queried body does not exist in drupal, add it.
            if ($doesExist == false) {
                //create body
                $addBody = new CharacterSheet($obj->{'bodyLabel'}->{'value'},
                    $obj->{'body'}->{'value'});
                $addId = $this->entity_Mgr->createEntity($addBody);

                /** reload addBody (CharacterSheet) as $addNodeInterface (NodeInterface) due to hook
                 * changes when saving, then seed it with init data from neptune */
                $addNodeInterface = Node::load($addId);
                $c_mgr->updateCharacterSheet($addNodeInterface);
            }

        }
    }

    private function importPortfolios(){

    }

    private function importLegislation(){

    }

}