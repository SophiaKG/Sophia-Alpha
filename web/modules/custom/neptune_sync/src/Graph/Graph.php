<?php


namespace Drupal\neptune_sync\Graph;


class Graph
{
    protected $file_location;
    protected $deployment_path;

    /**
     * Graph constructor.
     * @param $file_location
     * @param $deployment_path
     */
    public function __construct($file_location, $deployment_path)
    {
        $this->file_location = $file_location;
        $this->deployment_path = $deployment_path;
    }

    /**
     * @return mixed
     */
    public function getFileLocation()
    {
        return $this->file_location;
    }

    /**
     * @return mixed
     */
    public function getDeploymentPath()
    {
        return $this->deployment_path;
    }


}