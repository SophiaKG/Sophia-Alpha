<?php

namespace Drupal\neptune_sync\Querier;

/**
 * Class Query
 * @package Drupal\neptune_sync\Querier
 * @author Alexis harper| DoF
 * A model class for storing query information
 *
 */
class Query {

    protected $query;

    //Is the endpoint of the db to query
    protected $destination;
    /* where to put the resulting file relative to
     * [WEB_ROOT]/drupal8/web/ */
    protected $output_path;

    public function __construct($dest, $out_path=null){

        $this->destination = $dest;
        $this->output_path = $out_path;
    }

    public function setQuery($query){
        $this->query = $query;
    }
    
    public function getQuery(){
        return $this->query;
    }

    public function getDestination(){
        return $this->destination;
    }

    public function getOutputPath(){
        return $this->output_path;
    }
}
