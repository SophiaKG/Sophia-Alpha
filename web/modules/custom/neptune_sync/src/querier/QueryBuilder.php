<?php

namespace Drupal\neptune_sync\querier;

use Drupal\neptune_sync\Graph\GraphFilters;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;

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

        //Form selection part of query
        $sub_q =
            '{ ?subject ?predicate1 "' . $query_start_label . '" . ' .
            '?subject ?predicate2 ?label .}';

        return self::buildMirroredQuery($query_name, $sub_q);
    }

    public static function buildCustomLocalGraph($query_name, GraphFilters $filters){

        Helper::log('loop setup');
        $sub_q = self::expandGraphToK($filters);

        return self::buildMirroredQuery($query_name, $sub_q);
    }

    /**
     * Builds a select statement that grows by $k
     * It is formats as:
     *       ?a1  ?p1 ?[STARTNODE] .
     *       ?a1  ?p2* ?a2 .
     *       ?a2  ?p3* ?a3 .
     *       ?a3  ?p4* ?a4 .
     *
     * @param GraphFilters $filters
     *      Filters passed in from the form to customise how the query is built
     *
     * @return string
     *      The built sub-query (select element) to k-neighbourhood
     */
    private static function expandGraphToK(GraphFilters $filters){

        $q = '{ ?a1 ?predicate0 "' . $filters->start_node . '" . ';

        Helper::log('just before loop');
        //keep looping, feeding the query into itself vi $c + 1 till K is reached
        for($c = 1; $c <= $filters->steps; $c++){
            $q .= '?a' . (string)$c . ' ?predicate' . (string)$c . '* ?a' . (string)($c + 1) . ' . ';
            Helper::log('in loop', $c);
        }
        Helper::log('post-loop');

        //close the query
        $q .= '}';

        return $q;
    }

    private static function buildMirroredQuery($query_name, $mirrored_q){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT,
            self::GRAPH_WORKING_DIR . $query_name . '.rdf');

        //XXX this is just a test, to remove or find a proper work around
        $qr = str_replace('*', '', $mirrored_q );

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL .
            'CONSTRUCT ' . $qr .
            'WHERE ' . $mirrored_q
        );
        return $q;
    }
}