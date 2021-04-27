<?php


namespace Drupal\neptune_sync\Data;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\views\Plugin\views\field\Boolean;

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

    /**Processes the selected checkboxes to rebuild the site with the desired
     *   sync data selected by a user.
     *
     * @param array $filters passed in from the neptune sync from found at admin/syn
     * @uses \Drupal\neptune_sync\Form\DataSyncForm
     */
    public function formController(array $filters){

        Helper::log("preparing to sync data--");

        //Wipe data controller. Less code efficient but more readable.
        $wipeNodeID = [];
        /**
         * @TODO doing 4 array merges and db queries isnt create, fix this by building a
         *  dynamic ent query
         */
        foreach ($filters["wipe_data"] as $field) {
            Helper::log("in node delete, field is: " , false, $field);
            switch (strval($field)) { //strval as  form array 0 converts to true
                case "bodies":
                    Helper::log("adding all bodies from drupal to wipe", true);
                    $wipeNodeID = array_merge($wipeNodeID,
                        $this->entity_Mgr->getNodeTypeId(SophiaGlobal::BODIES));
                    break;
                case "legislation":
                    Helper::log("adding all legislation from drupal to wipe", true);
                    $wipeNodeID = array_merge($wipeNodeID,
                        $this->entity_Mgr->getNodeTypeId(SophiaGlobal::LEGISLATION));
                    break;
                case "portfolios":
                    Helper::log("adding all portfolios from drupal to wipe", true);
                    $wipeNodeID = array_merge($wipeNodeID,
                        $this->entity_Mgr->getNodeTypeId(SophiaGlobal::PORTFOLIO));
                    break;
                case "cooperative_relationships":
                    Helper::log("adding all cooperative_relationships from drupal to wipe", true);
                    $wipeNodeID = array_merge($wipeNodeID,
                        $this->entity_Mgr->getNodeTypeId(SophiaGlobal::COOPERATIVE_RELATIONSHIP));

                    break;
                case "all":
                    Helper::log("adding all data from drupal to wipe", true);
                    $wipeNodeID = $this->entity_Mgr->getAllNeptunetypesId();
                    break;
                default:
                    break;
            }
        }

        Helper::log("wipe id = " , false, $wipeNodeID);
        if($wipeNodeID) //if we have ids to delete.
            $this->wipeNodes($wipeNodeID, false);

        //Add empty records
        foreach ($filters["sync_node_creation"] as $field) {
            Helper::log("in node create, field is: " , false, $field);
            switch (strval($field)) {
                case "bodies":
                    $this->importFromQuery(QueryTemplate::getBodies(),
                        SophiaGlobal::BODIES);
                    break;
                case "legislation":
                    $this->importFromQuery(QueryTemplate::getLegislations(),
                        SophiaGlobal::LEGISLATION);
                    break;
                case "portfolios":
                    $this->importFromQuery(QueryTemplate::getPortfolios(),
                        SophiaGlobal::PORTFOLIO);
                    break;
                case "all":
                    $this->importFromQuery(QueryTemplate::getPortfolios(),
                        SophiaGlobal::PORTFOLIO);
                    $this->importFromQuery(QueryTemplate::getLegislations(),
                        SophiaGlobal::LEGISLATION);
                    $this->importFromQuery(QueryTemplate::getBodies(),
                        SophiaGlobal::BODIES);
                    break;
                default:
                    break;
            }
        }

        //Sync fields in existing bodies?
        if($filters["sync_neptune_data"]) {
            $c_mgr = new CharacterSheetManager();
            $c_mgr->updateAllCharacterSheets();
            Helper::log("Completed data sync cycle of all bodies in drupal", true);
        }

        Helper::log("Sync complete", true);
    }

    /**
     * Deletes bulk amount of nodes as efficently as possible.
     * @param $nodeIds string[] nid of nodes to delete
     * @param bool $doBulk Delete nodes in one operation or if deleting them one by one
     *  is required (incase bulk fails)
     * @return bool if an exception occurred
     * @throws EntityStorageException
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function wipeNodes($nodeIds, bool $doBulk){

        if(!sizeof($nodeIds) > 0 || !$nodeIds){
            Helper::log("Attempting to wipe data failed as no 
                id's were passed in");
            return false;
        }

        Helper::log("Deleting selected Neptune data...",true);
        try {
            $storage_handler = \Drupal::entityTypeManager()->getStorage(
                SophiaGlobal::NODE);
            Helper::log("Loading Ids...");
            $entities = $storage_handler->loadMultiple($nodeIds);
            Helper::log("Deleting the selected Neptune data...");
            if ($doBulk){
                Helper::log("Run mode: Bulk");
                $storage_handler->delete($entities);
            } else {
                Helper::log("Run mode: One-by-one");
                $count = 0;
                foreach($entities as $ent) {
                    Helper::log("Deleting node: " . $ent->id() . "\t count: "
                        . ++$count . "\t" . $ent->label());
                    $storage_handler->delete(array($ent));
                }
            }
        } catch (InvalidPluginDefinitionException|PluginNotFoundException|
            EntityStorageException $e) {

            Helper::log("Err508 - Error deleting nodes during mass delete. Exiting. Error: " . $e);
            return false;
        }

        Helper::log("Finished deleting the selected neptune data", true);
        return true;
    }

    /**@deprecated replaced by $this->formController and new dataSyncForm
     * @param bool $wipeData
     */
    public function importNeptuneData(bool $wipeData){

        if($wipeData)
            $this->wipeNodes($this->entity_Mgr->getAllNeptunetypesId());

        $this->importFromQuery(QueryTemplate::getPortfolios(), SophiaGlobal::PORTFOLIO);
        $this->importFromQuery(QueryTemplate::getLegislations(), SophiaGlobal::LEGISLATION);
        $this->importFromQuery(QueryTemplate::getBodies(), SophiaGlobal::BODIES);

        Helper::log("Finished creating all records", true);
    }

    /**
     *
     *
     * @TODO addcount triggers on and update to node not just a create due to getEntityIdBulk()
     *  returning a non 0 id if the entity already exist.
     * @param Query $query
     * @param $subType
     * @return bool
     */
    private function importFromQuery(Query $query, $subType){

        $json = $this->query_Mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        $addCount = 0;
        $skipCount = 0;
        foreach ($jsonObj->{'results'}->{'bindings'} as $obj) {
            $addNode = new Model\Node(
                $obj->{'objLabel'}->{'value'},
                $obj->{'obj'}->{'value'}, $subType);
            $nid = $this->entity_Mgr->getEntityId($addNode, true, true);
            if($nid) {
                $addCount++;
                Helper::log($addNode->getSubType() . " added through import. Id: " .
                    $nid . " Title: " . $addNode->getTitle() . " Running total: " .
                    $addCount . " added, " . $skipCount . " skipped", true);
            }
            else
                $skipCount++;

        }
        Helper::log($subType . " Records imported!", true);
        return true;
    }
}