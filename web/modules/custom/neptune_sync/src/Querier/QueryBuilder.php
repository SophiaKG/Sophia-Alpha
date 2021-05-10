<?php

namespace Drupal\neptune_sync\Querier;

use Drupal\neptune_sync\Data\Model\Node;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;

/**
 * Class QueryBuilder
 * @static
 * @package Drupal\neptune_sync\Querier
 * @author: Alexis Harper | DoF
 * This class is for building dynamic SPARQL queries
 */
class QueryBuilder {

    const GRAPH_WORKING_DIR = 'sites/default/files/graphs/';

    /**Checks if the node has a given property, binded by an authority
     * @deprecated never used?
     * @param NodeInterface $node The node to check the property from.
     * @param $term string The property to check if it exist.
     * @return Query Returns a true or false in the form of if the node has the given propery in neptune.
     */
   /*public static function checkTerm(NodeInterface $node, $term){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ' .
                self::getValidatedAuthorityPart(self::getUri($node, "ns2"), $term) .
            ' }');

        return $q;
    }*/

    /**
     * @uses getValidatedAuthorityPart()
     * @uses getStaffingPart()
     * @param $askClauseFunc string see uses
     * @return Query
     */
    public static function buildAskQuery($askClauseFunc){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ' .
                $askClauseFunc .
            ' }');
        return $q;
    }

    /**
     * Checks if a passed in node (Government Body) is part of a ns1 class
     *
     * @param NodeInterface $node the node to run the query for
     * @param $is_a string the NS2 class we are testing against
     * @return Query
     *
     */
    public static function checkAskBody(NodeInterface $node, String $is_a) {

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ' .
                self::getUri($node, "ns2") . ' a ns2:' . $is_a .
        ' }');

        return $q;
    }

    /**
     * Finds the single relevant portfolio for a Government body
     * Uses Graph1 & NS2
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_1
     * @param NodeInterface $node The node to get the portfolio  for
     * @return Query The query ready to execute
     * Sparql reference: https://aws-neptune-sophia-dev.notebook.ap-southeast-2.sagemaker.aws/notebooks/Neptune/Sophia%20Alpha%20QueryBuilder%20refrence.ipynb
     */
    public static function getBodyPortfolio(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?port ?portlabel ' .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { ' .
                '?auth ns2:fallsUnder ?port. ' .
                '?port rdfs:label ?portlabel. ' .
                '?auth ns2:binds ' . self::getUri($node, 'ns2') . '. ' .
                //'?port ns2:live true. ' .  /* @TODO temp disabled as on a freash scrape an AAO can be out of sync with the flipchart authority */
                '?flip ns2:witnesses ?auth. ' .
                '?flip a ns2:FinanceEntityList. ' .
                '?flip ns2:live true. ' .
            '} ORDER BY DESC(?datetime) ' .
            'LIMIT 1');

        return $q;
    }

    /**
     * Gets the legislation for a Government Body
     * Graph0
     *
     * legislation0 = the actual legislation type
     * legislation = the legislation series
     *
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_0
     * @param NodeInterface $node
     * @return Query
     */
    public static function getBodyLegislation(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?legislation ?legislationLabel ' .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { '.
                '?authority  ns2:bindsTo ' . self::getUri($node, 'ns2') . '. ' .
                '?authority  ns2:binds ?est. ' .
                '?est a ns2:Establishment. ' .
                '?legislation0 ns2:grants ?authority . ' .
                '?legislation0 ns2:hasSeries ?legislation. ' .
                '?legislation ns2:canonicalName ?legislationLabel. ' .
            '}');
        return $q;
    }

    /**
     * Gets the names a entity has been referred to but is not it's "correct" name
     *
     * @param NodeInterface $node either Body|Portfolio|legislation
     * @return Query can return a result of 0..n
     */
    public static function getAliases(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?label ' .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { ' .
                self::getUri($node, 'ns2') . ' rdfs:label ?label. ' .
            '}');
        return $q;
    }

    /**
     * Gets the url resource of a Node (either legislation or commonwealth body)
     * @TODO need to re-examine links using graph1 and thus lead bodies at a different date
     *
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_0
     * @param NodeInterface $node
     * @return Query
     */
    public static function getResourceLink(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?link ' .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { '.
                self::getUri($node, 'ns2') . " " .
                    SophiaGlobal::IRI['ns2']['prefix'] . 'webDataSource ?link.' .
            '}');
        return $q;
    }

    /**
     * Build a local graph for 2 steps of the RDF object of a node (government body, legislation, portfolio)
     *
     * @param NodeInterface $node
     * @return Query
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
     * Generate a usable neptune uri
     * As the field field_neptune_uri stores the uri based on the long form
     *  Uri that the node was imported from, we need to use strrchr to trim of the
     *  extended form uri and just get the local name before concatenating it to the user
     *  given namespace
     *
     * @param NodeInterface $node the node to get the uri for
     * @param String $prefix The prefix used to generate the uri (usually ns1|ns2)
     * @return string Neptune ready uri in the form of PREFIX:LocalName
     */
    public static function getUri(NodeInterface $node, String $prefix){
        return SophiaGlobal::IRI[$prefix]['prefix'] .
            substr(strrchr($node->get("field_neptune_uri")->getString(), "#"), 1);
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
            $q .= 'VALUES ?val {ns2:CommonwealthAgent ns1:CommonwealthBody} ';
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

    /**
     * Builds a query for selecting the neighbours of a given node to one step.
     *
     * @deprecated from ontologyViz days
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

    /**
     * @deprecated replaced with buildCustomLocalGraph()
     * @param $query_name
     * @param $mirrored_q
     * @return Query
     */
    private static function buildMirroredQuery($query_name, $mirrored_q){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT,
            self::GRAPH_WORKING_DIR . $query_name . '.rdf');

        //XXX this is just a test, to remove or find a proper work around
        $qr = str_replace('*', '', $mirrored_q );

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'CONSTRUCT ' . $qr .
            'WHERE ' . $mirrored_q
        );
        return $q;
    }

    /**
     * @deprecated never used?
     */
    public static function checkEmploymentType(NodeInterface $node, $term){
        self::getStaffingPart(self::getUri($node, "ns2"), $term);
    }

}
