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


    public function wipeNodes(){

        Helper::log("Deleting all Neptune data...",true);

        $nodeIds = $this->entity_Mgr->getAllNeptunetypesId();
        try {
            $storage_handler = \Drupal::entityTypeManager()->getStorage(
                SophiaGlobal::NODE);
            $entities = $storage_handler->loadMultiple($nodeIds);
            $storage_handler->delete($entities);
        } catch (InvalidPluginDefinitionException|PluginNotFoundException|
            EntityStorageException $e) {

            Helper::log("Err508 - Error deleting nodes during mass delete. Exiting. Error: " . $e);
            return false;
        }

        Helper::log("Finished deleting all neptune data", true);
        return true;
    }

    public function importNeptuneData(bool $wipeData){

        //if($wipeData)
          //  $this->wipeNodes();

        $this->importFromQuery(QueryTemplate::getPortfolios(), SophiaGlobal::PORTFOLIO);
       // $this->importFromQuery(QueryTemplate::getLegislations(), SophiaGlobal::LEGISLATION);
       // $this->importFromQuery(QueryTemplate::getBodies(), SophiaGlobal::BODIES);
    }

    public function importFromQuery(Query $query, $subType){

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
        Helper::log("Records imported!", true);
        return true;
    }

    private function importPortfolios(){

        $query = QueryTemplate::getPortfolios();
        $json = $this->query_Mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

    }

    private function importLegislation(){

    }

}