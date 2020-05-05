<?php

namespace Drupal\neptune_sync\querier;

/**
*
*/
class Query {

    protected $query;
    //Is the endpoint of the db to query
    protected $destination;
    /* where to put the resulting file relative to
       [WEB_ROOT]/drupal8/web/module/custom/[MODULE_NAME] */
    protected $output_Path;

    public function __construct($dest, $out_path)
    {
        $this->destination = $dest;
        $this->output_path = $out_path;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }
    
    public function getQuery()
    {
        return $this->query;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setOutputPath($path)
    {
        $this->output_Path - $path;
    }

    public function getOutputPath()
    {
        return $this->output_path;
    }
}
