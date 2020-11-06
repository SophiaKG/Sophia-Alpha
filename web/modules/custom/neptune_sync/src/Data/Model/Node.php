<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Utility\SophiaGlobal;

class Node implements \Drupal\neptune_sync\Data\DrupalEntityExport
{
    protected $title; //human readable identifier
    protected $idKey; //key that binds the node to neptune
    protected $nodeType;

    /**
     * Node constructor.
     * @param String $title
     * @param $idKey
     * @param String $nodeType
     */
    public function __construct($title, $idKey, $nodeType)
    {
        $this->title = $title;
        $this->idKey = $idKey;
        $this->nodeType = $nodeType;
    }

    public function getTitle(){
        return $this->title;
    }

    public function getIdKey(){
        return $this->idKey;
    }

    public function getEntityType()
    {
        return SophiaGlobal::NODE;
    }

    public function getSubType()
    {
        return $this->nodeType;
    }

    public function getEntityArray()
    {
        return array();
    }
}