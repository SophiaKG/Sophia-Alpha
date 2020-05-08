<?php


namespace Drupal\neptune_sync\querier;

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

    /**APPEND PREFIX LIST*
     * @param $query_name
     * The hashed identifier of the query
     * @param $query_start_label
     * A string of the nodes title (i.e. a triples label)
     * @return Query
     * The query, ready to execute
     */
    public static function buildLocalGraph($query_name, $query_start_label){
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT,
            SELF::GRAPH_WORKING_DIR . $query_name . '.rdf');

        $sub_q =
            '{ ?subject ?predicate1 "' . $query_start_label . '" . ' .
            '?subject ?predicate2 ?label .}';

        $q->setQuery(
            SophiaGlobal::PREFIX_ALL .
            'CONSTRUCT ' . $sub_q .
            'WHERE ' . $sub_q
        );

        return $q;
    }

}