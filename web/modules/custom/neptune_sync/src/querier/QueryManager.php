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

    /**
     *
     */
    public function __construct()
    {
        QueryTemplate::init();
        //\drupal::logger('neptune_sync')->alert('post init' . var_dump($queries));
    }

    /**
     *
     */
    public function syncAllDatasets()
    {
        $q = QueryTemplate::$queries['getLabels'];
        //var_dump($q);
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getLegislations'];
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getBodies'];
        $this->runQuery($q);
    }

    /*
     * Separated from run query for encapsulation principles despite having similar
     * functionality
     */
    public function runCustomQuery($query)
    {
        $this->runQuery($query);
    }

    protected function runQuery($query)
    {
        $cmd = 'curl -X POST --data-binary "query=' . $query->getQuery() . '" '
                . $query->getDestination() . ' > ' . $query->getOutputPath();
        $res = shell_exec($cmd);
        \drupal::logger('neptune_sync') ->notice("executed command: " . $cmd . "\nResults: " . $res);
        //var_dump($cmd);
    }
}

    
