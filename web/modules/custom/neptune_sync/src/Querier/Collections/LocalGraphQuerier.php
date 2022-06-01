<?php


namespace Drupal\neptune_sync\Querier\Collections;


use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\NodeInterface;

class LocalGraphQuerier{

    /**
     * Build a local graph (CONSTRUCT) for 2 steps from the RDF object of a node
     *  (government body, legislation, portfolio)
     *
     * @param NodeInterface $node The node (Body|Portfolio|legislation) to build
     *  the graph for
     * @return Query the executed query in turtle RDF
     */
    public static function buildCustomLocalGraph(NodeInterface $node){

        Helper::log('loop setup for ' . $node->getTitle());

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'CONSTRUCT ' . self::expandGraphToK($node->getTitle(), false) .
            'WHERE ' . self::expandGraphToK($node->getTitle(), true)
        );

        return $q;
    }

    /**
     * @TODO fix this, k is hard coded to 2
     * Builds a select statement that grows by $k
     * It is formats as:
     *       ?a1  ?p1 ?[STARTNODE] .
     *       ?a1  ?p2* ?a2 .
     *       ?a2  ?p3* ?a3 .
     *       ?a3  ?p4* ?a4 .
     *
     * @param string $start_node The origin node (government body) to start the graph from
     * @param bool $build_where If we are building the where clause. If false we are building the select
     * @return string The built sub-query (select element) to k-neighbourhood
     */
    private static function expandGraphToK(string $start_node, bool $build_where){

        //start label of query, go to it's subject, subject must be a body as labels arnt unique
        $q = '{ ?a1 ?predicate0 "' . $start_node . '" . ';
        if($build_where) {
            $q .= 'VALUES ?val {ns2:CommonwealthAgent ns2:CommonwealthBody} ';
            $q .= '?a1 a/rdfs:subClassOf* ?val. ';
        }

        $debug_where = $build_where ? 'true' : 'false';
        Helper::log('just before loop| where = ' . $debug_where);
        //keep looping, feeding the query into itself vi $c + 1 till K is reached
        for($c = 1; $c <= 2; $c++){
            if($build_where)
                $q .= 'OPTIONAL {';
            $q .= '?a' . (string)$c . ' ?predicate' . (string)$c . ' ?a' . (string)($c + 1);
            if ($build_where)
                $q .= ' }';
            $q .=' . ';
            Helper::log('in loop', $c);
        }
        Helper::log('post-loop| where = ' . $debug_where);

        //close the query
        $q .= '}';

        return $q;
    }

}