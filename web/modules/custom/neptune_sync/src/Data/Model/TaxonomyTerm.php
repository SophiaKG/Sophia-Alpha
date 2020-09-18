<?php


namespace Drupal\neptune_sync\Data;


class TaxonomyTerm implements DrupalEntityExport
{
    /** @var String */
    protected $name;

    /** @var String */
    protected $vid;


    /**
     * @return String
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param String $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return String
     */
    public function getVid(): string
    {
        return $this->vid;
    }

    /**
     * @param String $vid
     */
    public function setVid(string $vid): void
    {
        $this->vid = $vid;
    }

    public function getEntityType() {
        return 'taxonomy_term';
    }

    public function getSubType() {
        // TODO: Implement getSubType() method.
    }

    public function getEntityArray() {
        return array(
            'name'  => $this->name,
            'vid'   => $this->vid,
        );
        // TODO: Implement getEntityArray() method.
    }
}