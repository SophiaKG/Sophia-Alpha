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
class QueryBuilder
{
    const GRAPH_WORKING_DIR = 'sites/default/files/graphs/';

    /**
     * Checks if a passed in node (Government Body) is part of a ns1 class
     *
     * @param NodeInterface $node the node to run the query for
     * @param $is_a string the NS1 class we are testing against
     * @return Query
     *
     * @TODO pass in IrI for a more powerful check
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
     * @TODO WIP
     * @param NodeInterface $node
     * @return Query
     */
    public static function checkPsAct(NodeInterface $node) {

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ?subject rdfs:label "' . $node->getTitle() . '" . ' .
            ' ns1:PublicServiceAct1999 ns1:Enables ?subject . }');
        //'select distinct ?b2l from <http://aws.amazon.com/neptune/vocab/v001> where { ?ct rdfs:label "Public Service Act 1999". ?auth ?e ?act. ?auth a ns2:Authority. ?auth ?e2 ?b2. ?b2 rdfs:label ?b2l}'

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
                '?body rdfs:label "' . $node->getTitle() . '" . ' .
                '?body a/ rdfs:subClassOf* ns2:CommonwealthAgent. ' .
                '?body ns2:FallsUnder ?port. ' .
                '?port ns2:CanonicalName ?portlabel. ' .
                '?authority ns2:Binds ?est. ' .
                '?est a ns2:Establishment. ' .
                '?authority ns2:BindsTo ?port. ' .
                '?aao ns2:Grants ?authority. ' .
                '?event ns2:Empowers ?aao. ' .
                '?event ns2:startsAtOrAfter ?datetime. ' .
            '} ORDER BY DESC(?datetime) ' .
            'LIMIT 1');
        return $q;
    }

    /**
     * Gets the legislation for a Government Body
     * Graph0
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
                '?legislation rdfs:label ?legislationLabel. ' .
                '?legislation a ns2:Series. ' .
                '?legislation ns2:Grants ?authority. ' .
                '?authority ns2:Binds ?est. ' .
                '?est a ns2:Establishment. ' .
                '?authority ns2:BindsTo ?body. ' .
                '?body a ns2:CommonwealthBody. ' .
                '?body rdfs:label "' . $node->getTitle() . '". ' .
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

        switch ($node->getType()){
            case SophiaGlobal::LEGISLATION:
                $type = "Portfolio";
                break;
            case SophiaGlobal::BODIES:
            default:
                $type = "CommonwealthBody";
                break;
        }

        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?link ' .
            'FROM ' . SophiaGlobal::GRAPH_0 . ' ' .
            'WHERE { '.
                '?object rdfs:label "' . $node->getTitle() . '". ' .
                '?object a ' . SophiaGlobal::IRI['ns1']['prefix'] . $type . '. ' .
                '?object ' .  SophiaGlobal::IRI['ns1']['prefix'] . 'WebDataSource ?link.' .
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
    private static function getUri(NodeInterface $node, String $prefix){
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
}