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

        //build query form (SELECT | CONSTRUCT)
        if($flags & self::BUILD_GRAPH){
            $queryForm = self::constructCoopGraphStatement();
        } else { //select query
            $selectKey['default'] = array(
                '?sendBody',
                '?recBody',
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
                " WHERE { { " .
                    $valueStr .
                    self::whereCoopGraphStatement('?sendBody') .
                " } UNION { " .
                    $valueStr .
                    self::whereCoopGraphStatement('?recBody') .
                " } }";

        } else {

            $whereClause = "";
            if ($flags & self::OUTGOING_PROGRAMS)
                $whereClause = self::whereCoopGraphStatement("?sendBody");
            if ($flags & self::INCOMING_OUTCOMES)
                $whereClause = self::whereCoopGraphStatement("?recBody");

            $whereStr =
                "WHERE { " .
                $valueStr .
                $whereClause .
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
     * Builds a contained construction clause of a Sparql query based on select keys
     *
     * @param array string[] selectKey in form of [sendLab, recLabel, ?sendLabUnion?, ?recLabelUnion?]
     * @return string the construction statement of the query in the form of "CONSTRUCT { [DATA] }"
     */
    private static function constructCoopGraphStatement(){

        return
            'CONSTRUCT {' .
                '?sendBody ns2:Grants ?prog. ' .
                '?prog ns2:Enables ?outcome. ' .
                '?outcome ns2:Empowers ?recBody. ' .
                '?prog rdfs:label ?progLabel. ' .
                '?outcome rdfs:label ?outcomeLabel. ' .
                '?prog ns2:Content ?progDesc. ' .
                '?outcome ns2:Content ?outcomeDesc. ' .
                '?sendBody rdfs:label ?sendBodyLab. ' .
                '?recBody rdfs:label ?recBodyLab. ' .
                '?recBody rdf:type ns2:CommonwealthBody. ' .
                '?sendBody rdf:type ns2:CommonwealthBody. ' .
                '?prog rdf:type ns2:Program. ' .
                '?outcome rdf:type ns2:Outcome. ' .
            '} ';
    }

    /**
     * Builds a contained where clause for a Sparql query based on what are the
     * starting and ending nodes.
     *
     * @param string $entityBinding the sparql variable to bind entities to. Usually sendBody or recBody
     * @return string The build where clause in the form of "[DATA]" Where clause is not included.
     */
    private static function whereCoopGraphStatement(string $entityBinding){

        return
            //Graph logic
            'BIND(?entities AS  ' . $entityBinding . '). ' .
            '?auth ns2:Binds ?prog. ' .
            '?auth ns2:BindsTo ?outcome. ' .        //gets outcome
            '?auth1 ns2:Binds ?prog. ' .            //gets a1 (start of query) and a2:(leads to lead body) from program
            '?auth1 ns2:BindsTo ?sendBody. ' .        //go over BindsTo to get to lead body (ie: commonwealthbody)
            '?auth2 ns2:Binds ?outcome. ' .         //get other auth that point to the outcome (ent2)
            '?auth2 ns2:BindsTo ?recBody. ' .        //get the rec. body from auth
            //get labels
            '?prog ns2:Content ?progDesc. ' .       //get the description of the program
            '?outcome ns2:Content ?outcomeDesc. ' . //get the description of the outcome
            '?sendBody rdfs:label ?sendBodyLab. '.  //ent label
            '?prog rdfs:label ?progLabel. ' .       //program label
            '?outcome rdfs:label ?outcomeLabel. ' . //outcome (purpose) lab
            '?recBody rdfs:label ?recBodyLab. ' .   //rec body
            //Apply filters to constrain to classes
            '?sendBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .  //Filters: super and all subclasses
            '?prog a ns2:Program. ' .
            '?outcome a ns2:Outcome. ' .
            '?recBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .
            '?auth a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth1 a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth2 a/rdfs:subClassOf* ns2:Authority. ';
    }
}