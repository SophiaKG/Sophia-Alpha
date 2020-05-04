<?php

namespace Drupal/neptune_sync/interface

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

    public setQuery($query){
        
        $this->query = $query;
    }
    
    public getQuery(){
        
        return $this->query;
    }

    public getDestination(){

        return $this->destination;
    }

    public getOutputPath(){
        
        return $this->output_path;
    }
}
