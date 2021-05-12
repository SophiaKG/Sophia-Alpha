<?php


namespace Drupal\neptune_sync\Data\Model;


use Drupal\neptune_sync\Data\DrupalEntityExport;
use Drupal\neptune_sync\Utility\SophiaGlobal;

class TaxonomyTerm implements DrupalEntityExport
{
    /** @var String */
    protected $name;

    /** @var String */
    protected $vid;

    /**
     * TaxonomyTerm constructor.
     * @param String $name
     * @param String $vid
     */
    public function __construct(string $name, string $vid){
        $this->name = $name;
        $this->vid = $vid;
    }

    /**
     * @return String
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @param String $name
     */
    public function setName(string $name): void{
        $this->name = $name;
    }

    /**
     * @return String
     */
    public function getVid(): string{
        return $this->vid;
    }

    /**
     * @param String $vid
     */
    public function setVid(string $vid): void{
        $this->vid = $vid;
    }

    public function getTitle(){
        return $this->name;
    }

    public function getIdKey(){
        return $this->name;
    }

    public function getEntityType() {
        return SophiaGlobal::TAXONOMY;
    }

    public function getSubType() {
        return $this->vid;
    }

    public function getEntityArray() {
        return array(
            'name'  => $this->name,
            'vid'   => $this->vid,
        );
    }

    public function getPublishStatus(){
        return 1;
    }
}