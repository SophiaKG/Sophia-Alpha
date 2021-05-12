<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Utility\SophiaGlobal;

class Node implements \Drupal\neptune_sync\Data\DrupalEntityExport
{
    protected $title; //human readable identifier
    protected $idKey; //key that binds the node to neptune
    protected $nodeType;
    /** @var int $publishStatus 0 = unpublished | 1 = published */
    protected $publishStatus;

    /**
     * Node constructor.
     * @param String $title
     * @param $idKey
     * @param String $nodeType
     */
    public function __construct($title, $idKey, $nodeType){
        $this->title = $title;
        $this->idKey = $idKey;
        $this->nodeType = $nodeType;
        $this->publishStatus = 1;
    }

    public function getTitle(){
        return $this->title;
    }

    public function getIdKey(){
        return $this->idKey;
    }

    public function getEntityType(){
        return SophiaGlobal::NODE;
    }

    public function getSubType(){
        return $this->nodeType;
    }

    public function getEntityArray(){
        return array(
            'title' =>  $this->getTitle(),
            'field_neptune_uri' => $this->getIdKey(),
        );
    }

    /**
     * @return int
     */
    public function getPublishStatus(): int{
        return $this->publishStatus;
    }

    /**
     * @param int $publishStatus
     */
    public function setPublishStatus(int $publishStatus): void{
        $this->publishStatus = $publishStatus;
    }


}