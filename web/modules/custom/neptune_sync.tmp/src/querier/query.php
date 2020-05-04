<?php

namespace Drupal\neptune_sync\querier;

/**
*
*/
class query implements queryInterface{

    protected $query;
    protected $destination;
    protected $output_Path;

    public function __construct($dest, $out_path){

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
