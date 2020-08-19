<?php

namespace Drupal\neptune_sync\Utility;

/**
 * Class SophiaGlobal
 * @package Drupal\neptune_sync\Utility
 * @author Alexis Harper | DoF
 * A class for storing global data for Sophia-Alpha but keeping it in a specific scope
 */
class SophiaGlobal
{

    /**
     * SPARQL prefixes
     * notes: prefixes appear truncated in logger but work fine
     * @todo: make this a linked array with the other cell holding its qid
     */
    private const PREFIX_NS1 = 'PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>';
    private const PREFIX_RDFS = 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>';
    private const PREFIX_OWL = 'PREFIX owl: <http://www.w3.org/2002/07/owl#>';

    public const PREFIX_ALL = self::PREFIX_NS1 . ' ' . self::PREFIX_RDFS . ' ' . self::PREFIX_OWL;

    /**
     * GRAPHS:
     */
    public const GRAPH_0 = '<http://aws.amazon.com/neptune/vocab/v000>';
    public const GRAPH_1 = '<http://aws.amazon.com/neptune/vocab/v001>';
}
