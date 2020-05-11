<?php

namespace Drupal\neptune_sync\querier;

use Drupal\neptune_sync\Graph\GraphFilters;
use Drupal\neptune_sync\Utility\SophiaGlobal;

/**
 * Class QueryBuilder
 * @package Drupal\neptune_sync\querier
 * @author: Alexis Harper | DoF
 * This class is for building dynamic SPARQL queries
 */
class QueryBuilder
{
    const GRAPH_WORKING_DIR = 'sites/default/files/graphs/';

    /**
     * Builds a query for selecting the neighbours of a given node to one step.
     *
     * @param $query_name
     * The hashed identifier of the query
     * @param $query_start_label
     * A string of the nodes title (i.e. a triples label)
     * @return Query
     * The query, ready to execute
     */
    public static function buildLocalGraph($query_name, $query_start_label){

        //Build query base
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT,
            SELF::GRAPH_WORKING_DIR . $query_name . '.rdf');

        //Form selection of query
        $sub_q =
            '{ ?subject ?predicate1 "' . $query_start_label . '" . ' .
            '?subject ?predicate2 ?label .}';

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL .
            'CONSTRUCT ' . $sub_q .
            'WHERE ' . $sub_q
        );

        return $q;
    }

    public static function buildCustomLocalGraph($query_name, $query_start_label, GraphFilters $filters){

        //Build query base
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT,
            SELF::GRAPH_WORKING_DIR . $query_name . '.rdf');

        //Form selection of query
        $sub_q = self::expandGraphToK($filters->steps, $query_start_label);

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL .
            'CONSTRUCT ' . $sub_q .
            'WHERE ' . $sub_q
        );
        return $q;
    }

    /**
     * Builds a select statement that grows by $k
     * It is formats as:
     *       ?a1  ?p1 ?[STARTNODE] .
     *       ?a1  ?p2 ?a2 .
     *       ?a2  ?p3 ?a3 .
     *       ?a3  ?p4 ?a4 .
     *
     * @param $k
     *      The amount of links to traverse from the start node (i.e. k-Neighbourhood)
     * @param $query_start_label
     *      The rdf node to start the expansion on
     * @return string
     *      The built sub-query (select element) to k-neighbourhood
     */
    private static function expandGraphToK($k, $query_start_label){

        $q = '{ ?a1 ?predicate1 "' . $query_start_label . '" . ';

        //keep looping, feeding the query into itself vi $c + 1 till K is reached
        for($c = 1; c <= $k; $c++){
            $q .= '?a' . $c . ' ?predicate' . $c . ' ?a' . $c + 1 . ' . ';
        }

        //close the query
        $q .= '}';

        return $q;
    }
}