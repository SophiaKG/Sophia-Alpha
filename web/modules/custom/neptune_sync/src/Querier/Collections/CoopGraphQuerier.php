<?php

namespace Drupal\neptune_sync\Querier\Collections;

use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;


/**
 * Class CoopGraphQuerier
 * A class for building cooperative relation queries dynamically via flags.
 * Sparql queries built from:
 *      https://aws-neptune-sophia-dev.notebook.ap-southeast-2.sagemaker.aws/notebooks/Neptune/D.S.%20cooperation%20work.ipynb
 * @static
 * @package Drupal\neptune_sync\Querier\Collections
 * @author Alexis Harper | DoF
 */
class CoopGraphQuerier{

    //flags
    const OUTGOING_PROGRAMS = 0x2; //Query outgoing relationships to the given nodes
    const INCOMING_OUTCOMES = 0x4; //Query incoming relationships to the given nodes
    const BUILD_GRAPH       = 0x8; //Whether to display the query as a graph or as a plain query


    /**
     * Public interface for using this class. Query is built from instructs passed as flags and nodes
     * which act as start points.
     *
     * @static
     * @param NodeInterface[] $nodes an array of nodes to execute the query over
     * @param $flags int OUTGOING_PROGRAMS | INCOMING_OUTCOMES | BUILD_GRAPH
     * @return Query The dynamic built query
     */
    public static function getCooperativeRelationships(array $nodes, $flags){

        //add values segment
        $valueStr = 'values ?entities { ';
        foreach ($nodes as $node)
            $valueStr .= QueryBuilder::getUri($node, "ns2") . ' ';
        $valueStr .= "} ";

        //build dynamic keys based on what type of query is being built
        $selectKey = self::buildCoopKeys($flags);

        //build query form (SELECT | CONSTRUCT)
        if($flags & self::BUILD_GRAPH){
            $queryForm = self::constructCoopGraphStatement($selectKey);
        } else { //select query
            $selectKey['default'] = array(
                '?sendBodyLab',
                '?recBodyLab',
                '?progLabel',
                '?progDesc',
                '?outcomeLabel',
                '?outcomeDesc',
            );

            $queryForm = "SELECT DISTINCT ";
            foreach ($selectKey as $key => $val)
                if ($key = 'default')               //build with default keys
                    foreach ($val as $subval)
                        $queryForm .= $subval . " ";
                else                                //add custom keys
                    if ($val != '?entities')        //Entities mapping should not be
                        $queryForm .= $val . " ";   //returned in a select query
            //as it was passed in
            $queryForm .= " ";
        }

        //Build where statement
        if($flags & self::OUTGOING_PROGRAMS && $flags & self::INCOMING_OUTCOMES){

            //union needed
            $whereStr =
                "WHERE { { " .
                    $valueStr .
                    self::whereCoopGraphStatement($selectKey['sendBody'],
                        $selectKey['recBody']) .
                " } UNION { " .
                    $valueStr .
                    self::whereCoopGraphStatement($selectKey['sendBodyUnion'],
                        $selectKey['recBodyUnion']) .
                " } }";

        } else {
            $whereStr =
                "WHERE { " .
                $valueStr .
                self::whereCoopGraphStatement($selectKey['sendBody'],
                    $selectKey['recBody']) .
                " }";
        }

        //Add together and build query
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            $queryForm . //SELECT|CONSTRUCT
            'FROM ' . SophiaGlobal::GRAPH_1 .
            $whereStr
        );

        return $q;
    }

    /**
     * Builds dynamic Sparql keys based on the type of query that needs building
     * based on the flags passed in.
     *
     * @param $flags int OUTGOING_PROGRAMS | INCOMING_OUTCOMES | BUILD_GRAPH
     * @return string[] selectKey in form of [sendLab, recLabel, ?sendLabUnion?, ?recLabelUnion?]
     *
     * Select keys does not mean query is a select query
     */
    private static function buildCoopKeys($flags){

        Helper::log("build coop query, flags = " . $flags);
        $selectKey = [];

        //if we need to union
        if($flags & self::OUTGOING_PROGRAMS && $flags & self::INCOMING_OUTCOMES){

            Helper::log("build keeps both");
            $selectKey['sendBody'] = '?entities';
            $selectKey['recBody'] = '?recBody';
            /*---- Union ----*/
            $selectKey['sendBodyUnion'] = '?sendBody';
            $selectKey['recBodyUnion'] = '?entities';


        } else { //if we don't
            Helper::log("build keys single");
            if ($flags & self::OUTGOING_PROGRAMS) {
                $selectKey['sendBody'] = '?entities';
            } else {
                $selectKey['sendBody'] = '?sendBody';
            }

            if ($flags & self::INCOMING_OUTCOMES) {
                $selectKey['recBody'] = '?entities';
            } else {
                $selectKey['recBody'] = '?recBody';
            }
        }
        
        return $selectKey;
    }

    /**
     * Builds a contained construction clause of a Sparql query based on select keys
     *
     * @param array string[] selectKey in form of [sendLab, recLabel, ?sendLabUnion?, ?recLabelUnion?]
     * @return string the construction statement of the query in the form of "CONSTRUCT { [DATA] }"
     */
    private static function constructCoopGraphStatement(array $selectKey){

        $retString =
            'CONSTRUCT {' .
            //'?sendBody ns2:Grants ?prog. ' .
            '?prog ns2:Enables ?outcome. ' .
            //'?outcome ns2:Empowers ?recBody. ' .
            '?prog rdfs:label ?progLabel. ' .
            '?outcome rdfs:label ?outcomeLabel. ' .
            '?prog ns2:Content ?progDesc. ' .
            '?outcome ns2:Content ?outcomeDesc. ' .
            '?sendBody rdfs:label ?sendBodyLab. ' . //new
            '?recBody rdfs:label ?recBodyLab. ' .  //new
            '?sendBody rdf:type ns2:CommonwealthBody. ' .
            '?recBody rdf:type ns2:CommonwealthBody. ' .
            '?prog rdf:type ns2:Program. ' .
            '?outcome rdf:type ns2:Outcome. ';

        //if this key exists, we must be building a union graph
        if(array_key_exists('sendBodyUnion', $selectKey))

            $selectKey['sendBody'] . '. ns2:Grants ?prog. ' .
            '?outcome ns2:Empowers ' . $selectKey['recBody'] . '. ';
            /*$retString .=
                '?sendBody rdfs:label ' . $selectKey['sendLabUnion']    . ". " .
                '?sendBody rdfs:label ' . $selectKey['sendLab']         . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabelUnion']   . ". ";*/
        else //Non-union graph
            $retString .=
                $selectKey['sendBody'] . ' ns2:Grants ?prog. ' .
                '?outcome ns2:Empowers ' . $selectKey['recBody'] . '. ';

                /*'?sendBody rdfs:label ' . $selectKey['sendLab']         . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". ";*/

        $retString .=  "} ";

        return $retString;
    }

    /**
     * Builds a contained where clause for a Sparql query based on what are the
     * starting and ending nodes.
     *
     * @param string $sent The origin node in which we stem the query from
     * @param string $rec The end point of the query, for which we use as the destination
     * @return string The build where clause in the form of "[DATA]" Where clause is not included.
     */
    private static function whereCoopGraphStatement(string $sent, string $rec){

        return
            //Graph logic
            '?auth ns2:Binds ?prog. ' .
            '?auth ns2:BindsTo ?outcome. ' .        //gets outcome
            '?auth1 ns2:Binds ?prog. ' .            //gets a1 (start of query) and a2:(leads to lead body) from program
            '?auth1 ns2:BindsTo ' . $sent . '. ' .  //go over BindsTo to get to lead body (ie: commonwealthbody)
            '?auth2 ns2:Binds ?outcome. ' .         //get other auth that point to the outcome (ent2)
            '?auth2 ns2:BindsTo ' . $rec . '. ' .   //get the rec. body from auth
            //get labels
            '?prog ns2:Content ?progDesc. ' .       //get the description of the program
            '?outcome ns2:Content ?outcomeDesc. ' . //get the description of the outcome
            $sent . ' rdfs:label ?sendBodyLab. '. //ent label
            '?prog rdfs:label ?progLabel. ' .       //program label
            '?outcome rdfs:label ?outcomeLabel. ' . //outcome (purpose) lab
            $rec . ' rdfs:label ?recBodyLab. ' . //rec body
            //Apply filters to constrain to classes
            $sent . ' a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .  //Filters: super and all subclasses
            '?prog a ns2:Program. ' .
            '?outcome a ns2:Outcome. ' .
            $rec . ' a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .
            '?auth a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth1 a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth2 a/rdfs:subClassOf* ns2:Authority. ';
    }
}