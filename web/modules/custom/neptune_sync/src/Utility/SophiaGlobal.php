<?php

namespace Drupal\neptune_sync\Utility;

class SophiaGlobal
{

    /**
     * SPARQL prefixes
     * @todo post: is being truncated
     */
    private const PREFIX_NS1 = 'PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>';
    private const PREFIX_RDFS = 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>';
    const PREFIX_ALL = self::PREFIX_NS1 . ' ' . self::PREFIX_RDFS . ' ';

}
