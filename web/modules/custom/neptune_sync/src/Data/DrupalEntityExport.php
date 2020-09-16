<?php


namespace Drupal\neptune_sync\Data;


interface DrupalEntityExport
{
    public function getEntityType();
    public function getSubType();
    public function getEntityArray();
}