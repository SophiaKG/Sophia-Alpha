<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Utility\SophiaGlobal;

class Node implements \Drupal\neptune_sync\Data\DrupalEntityExport
{
    protected $title;
    protected $nodeType;

    /**
     * Node constructor.
     * @param $title
     * @param $nodeType
     */
    public function __construct($title, $nodeType)
    {
        $this->title = $title;
        $this->nodeType = $nodeType;
    }

    public function getLabelKey()
    {
        return $this->title;
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