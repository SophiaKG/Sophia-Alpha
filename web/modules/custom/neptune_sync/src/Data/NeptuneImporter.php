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

        $query = QueryTemplate::getBodies();
        $json = $this->query_Mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        $addCount = 0;
        $skipCount = 0;
        foreach ($jsonObj->{'results'}->{'bindings'} as $obj) {
            $addBody = new CharacterSheet($obj->{'bodyLabel'}->{'value'},
                $obj->{'body'}->{'value'});
            $nid = $this->entity_Mgr->getEntityId($addBody, true, true);
            if($nid) {
                $addCount++;
                Helper::log($addBody->getSubType() . " added through import. Id: " .
                    $nid . " Title: " . $addBody->getTitle() . " Running total: " .
                    $addCount . " added, " . $skipCount . " skipped", true);
            }
            else
                $skipCount++;

        }
        Helper::log("Records imported!", true);
        return true;
    }

    private function importPortfolios(){

    }

    private function importLegislation(){

    }

}