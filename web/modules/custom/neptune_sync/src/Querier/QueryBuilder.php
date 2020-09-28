<?php

namespace Drupal\neptune_sync\Querier;

use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;

/**
 * Class QueryBuilder
 * @package Drupal\neptune_sync\Querier
 * @author: Alexis Harper | DoF
 * This class is for building dynamic SPARQL queries
 */
class QueryBuilder
{
    const GRAPH_WORKING_DIR = 'sites/default/files/graphs/';

    public static function checkAskBody(NodeInterface $node, $is_a) {

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ?subject rdfs:label "' . $node->getTitle() . '" ; ' .
                        ' a ns1:' . $is_a . ' }');

        return $q;
    }

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

    /**TODO this needs documenting urgently
     * Graph0
     * @param NodeInterface $node
     * @return Query
     */
    public static function getBodyPortfolio(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?port ?portlabel ?datetime ' .
            'FROM ' . SophiaGlobal::GRAPH_0 . ' ' .
            'WHERE { ' .
                '?body rdfs:label "' . $node->getTitle() . '" .' .
                '?body a ns1:CommonwealthBody. ' .
                '?body ns1:FallsUnder ?port. ' .
                '?port rdfs:label ?portlabel. ' .
                '?aao ns1:Defines ?port. ' .
                '?event ns1:Empowers ?aao. ' .
                '?event ns1:startsAtOrAfter ?datetime. ' .
            '} ORDER BY DESC(?datetime) ' .
            'LIMIT 1');
        return $q;
    }

    /**
     * Graph0
     * @param NodeInterface $node
     * @return Query
     */
    public static function getBodyLegislation(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        //Form the entire query
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?legislationLabel ' .
            'FROM ' . SophiaGlobal::GRAPH_0 . ' ' .
            'WHERE { '.
                '?legislation rdfs:label ?legislationLabel. ' .
                '?legislation ?e ?body. ' .
                '?legislation a ns1:Legislation. ' .
                '?body a ns1:CommonwealthBody. ' .
                '?body rdfs:label "' . $node->getTitle() . '". ' .
            '}');
        return $q;
    }

    /**
     * @param NodeInterface|null $node
     * @return mixed
     * Workbook:
     * https://aws-neptune-sophia-dev.notebook.ap-southeast-2.sagemaker.aws/notebooks/Neptune/D.S.%20cooperation%20work.ipynb
     * @: New with add desc to term
     */
    public static function getCooperativeRelationships(NodeInterface $node = null){

        //get all relations for all nodes
        if ($node == null){
            $selectStr = ' SELECT DISTINCT ?ent1Label ?progLabel ?progDesc ?outcomeLabel ?outcomeDesc ?ent2label ' ;
            $bindStr = '?ent1Label. ';
        } else {
            $selectStr = ' SELECT DISTINCT ?progLabel ?progDesc ?outcomeLabel ?outcomeDesc ?ent2Label ';
            $bindStr = '"' . $node->getTitle() . '". ';
        }

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            $selectStr  .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { ' .
                //Graph logic
                '?auth ns2:Binds ?prog. ' .
                '?auth ns2:BindsTo ?outcome. ' .        //gets outcome
                '?auth1 ns2:Binds ?prog. ' .            //gets a1 (start of query) and a2:(leads to lead body) from program
                '?auth1 ns2:BindsTo ?sendBody. ' .      //go over BindsTo to get to lead body (ie: commonwealthbody)
                '?auth2 ns2:Binds ?outcome. ' .         //get other auth that point to the outcome (ent2)
                '?auth2 ns2:BindsTo ?recBody. ' .       //get the rec. body from auth
                //get labels
                '?prog ns2:Content ?progDesc. ' .       //get the description of the program
                '?outcome ns2:Content ?outcomeDesc. ' . //get the description of the outcom
                '?sendBody rdfs:label ' . $bindStr .    //ent label
                '?prog rdfs:label ?progLabel. ' .       //program label
                '?outcome rdfs:label ?outcomeLabel. ' . //outcome (purpose) lab
                '?recBody rdfs:label ?ent2Label.'  .    //rec body
                //Apply filters to constrain to classes
                '?sendBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .   //Filters: super and all subclasses
                '?prog a ns2:Program. ' .
                '?outcome a ns2:Outcome. ' .
                '?recBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .
                '?auth a/rdfs:subClassOf* ns2:Authority. ' .
                '?auth1 a/rdfs:subClassOf* ns2:Authority. ' .
                '?auth2 a/rdfs:subClassOf* ns2:Authority. ' .
            '}');
        return $q;
    }

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
     * @param GraphFilters $filters
     *      Filters passed in from the form to customise how the query is built
     *
     * @param bool $build_where
     * @return string
     *      The built sub-query (select element) to k-neighbourhood
     */
    private static function expandGraphToK(string $start_node, bool $build_where){

        //start label of query, go to it's subject
        $q = '{ ?a1 ?predicate0 "' . $start_node . '" . ';

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

    /**@deprecated replace with
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