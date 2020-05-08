<?php 

namespace Drupal\neptune_sync\querier;


/**
 * Class QueryManager
 * @package Drupal
 * @author Alexis Harper | DoF
 * A class for running SPAQRL querier from drupal to neptune, leaving a file
 * for feeds to read and import the query results. all query calls should go
 * through this class
 */
class QueryManager
{
    protected $query;

    public function __construct(){
        QueryTemplate::init();
    }

    /**
     * Creates json files based on SPARQL queries ready to import into
     * drupal via feeds
     */
    public function syncAllDatasets(){
        $q = QueryTemplate::$queries['getLabels'];
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getLegislations'];
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getBodies'];
        $this->runQuery($q);
    }

    /**
     * Separated from run query for encapsulation principles despite having similar
     * functionality
     * @param $query query
     *      The query to execute
     */
    public function runCustomQuery($query){
        $this->runQuery($query);
    }

    /**
     * Detail 2>&1 | tee  (https://www.php.net/manual/en/function.shell-exec.php)
     * @param $query
     */
    protected function runQuery($query){
        $cmd = 'curl -s -X POST --data-binary \'query=' . $query->getQuery() . '\' '
                . $query->getDestination() . " 2>&1 | tee " . $query->getOutputPath();
        $res = shell_exec($cmd);
        \drupal::logger('neptune_sync') ->notice("executed command: " . $cmd . "\nResults: " . $res);

    }
}

    
