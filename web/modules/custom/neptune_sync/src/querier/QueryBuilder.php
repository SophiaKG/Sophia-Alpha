<?php


namespace Drupal\neptune_sync\querier;

/**
 * Class QueryBuilder
 * @package Drupal\neptune_sync\querier
 * @author: Alexis Harper | DoF
 * This class is for building dynamic SPARQL queries
 */
class QueryBuilder
{
    const GRAPH_WORKING_DIR = 'sites/default/files/graphs';

    public static function buildLocalGraph($query_name, $query_start_node){
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT, SELF::GRAPH_WORKING_DIR . $query_name . '.rdf');
        $q->setQuery('PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#> ' .
            'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> ' .
            'SELECT ?object ' .
            'WHERE {?Entity a ns1:CommonwealthBody . ' .
            '?Entity rdfs:label ?object .}');
        return $q;
    }

}