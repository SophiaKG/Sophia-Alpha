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
    public const IRI = array(
        'ns1' => array(
            'prefix' => 'ns1:',
            'IRI' => 'PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>',
            'name' => 'ns1',
            'loc' => 'file:///home/andnfitz/GovernmentEntities.owl#'),
        'rdfs' => array(
            'prefix' => 'rdfs:',
            'IRI' => 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>',
            'name' => 'rdfs',
            'loc' => 'http://www.w3.org/2000/01/rdf-schema#'),
        'owl' => array(
            'prefix' => 'owl:',
            'IRI' => 'PREFIX owl: <http://www.w3.org/2002/07/owl#>',
            'name' => 'owl',
            'loc' => 'http://www.w3.org/2002/07/owl#'),
        'rdf' => array(
            'prefix' => 'rdf',
            'IRI' => 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>',
            'name' => 'rdf',
            'loc' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'),
        'ns2' => array(
            'prefix' => 'ns2:',
            'IRI' => 'PREFIX ns2: <file:///C:/SophiaBuild/data/OntologyFiles/GovernmentEntities.owl#>',
            'name' => 'ns2',
            'loc' => 'file:///C:/SophiaBuild/data/OntologyFiles/GovernmentEntities.owl#'),
    );

    public static function PREFIX_ALL(){

        $str = '';
        foreach (self::IRI as $iri){
            $str .= $iri['IRI'] . ' ';
        }
        return $str;
    }

    /**
     * GRAPHS:
     */
    public const GRAPH_0 = '<http://aws.amazon.com/neptune/vocab/v000>';
    public const GRAPH_1 = '<http://aws.amazon.com/neptune/vocab/v001>';

    /**
     * ENTITIES
     */
    /* Drupal entity machine name identifiers */
    public const NODE = 'node';
    public const TAXONOMY = 'taxonomy_term';

    /* Drupal nde type machine names */
    public const BODIES = 'bodies';
    public const LEGISLATION = 'legislation';
    public const PORTFOLIO = 'portfolios';
    public const COOPERATIVE_RELATIONSHIP = 'cooperative_relationships';

    /* Maintenance_bot user id */
    public const MAINTENANCE_BOT = 47;
}
