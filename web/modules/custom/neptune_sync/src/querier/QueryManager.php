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
    protected $output_path;
    protected $query_manager;

    /**
     *
     */
    public function __construct()
    {
        QueryTemplate::init();
    }


    /**
     *
     */
    public function syncAllDatasets()
    {
        $q = QueryTemplate::$queries['getLabels'];
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getLegislations'];
        $this->runQuery($q);
        $q = QueryTemplate::$queries['getBodies'];
        $this->runQuery($q);
    }

    protected function runQuery($query)
    {
        $cmd = 'curl -X POST --data-binary "query=' . $query->getQuery() . '" '
                . $query->getDestination() . ' > ' . $query->getOutputPath();
        shell_exec($cmd);
        \drupal::logger('neptune_sync') ->alert("executed command: " . $cmd);
    }
}

    
