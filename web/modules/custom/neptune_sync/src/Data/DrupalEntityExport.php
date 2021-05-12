<?php


namespace Drupal\neptune_sync\Data;


interface DrupalEntityExport
{
    public function getTitle();

    /**
     * @return string The id that binds the drupal entity to neptune, usually an RDF URI
     *  The id will be a textual label (usually a title) if its not bound to neptune.*/
    public function getIdKey();

    public function getEntityType();
    public function getSubType();
    public function getEntityArray();
    public function getPublishStatus();
}