<?php


namespace Drupal\neptune_sync\Data;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;

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

    protected $entityMgr;


     /**
     * NeptuneImporter constructor.
     */
    public function __construct(){
        $this->entityMgr = new EntityManager();
    }


    public function wipeNodes(){
        Helper::log("Deleting all Neptune data...",true);
        $nodes = $this->entityMgr->getAllNeptunetypes();
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

    private function importBodies(){

    }

    private function importPortfolios(){

    }

    private function importLegislation(){

    }

}