<?php


namespace Drupal\neptune_sync\Data;


interface DrupalEntityExport
{
    public function getLabelKey();
    public function getEntityType();
    public function getSubType();
    public function getEntityArray();
}