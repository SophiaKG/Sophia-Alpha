<?php


namespace Drupal\neptune_sync\Querier\Collections;

use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;


/**
 * Class CoopGraphQuerier
 * A class for building cooperative relation queries dynamically via flags
 * @package Drupal\neptune_sync\Querier\Collections
 * @author Alexis Harper | DoF
 */
class CoopGraphQuerier{

    const OUTGOING_PROGRAMS = 0x2;
    const INCOMING_OUTCOMES = 0x4;
    const BUILD_GRAPH       = 0x8;

    public static function getCooperativeRelationships(array $nodes, $flags){

        //add values segment
        $valueStr = 'values ?entities {';
        foreach ($nodes as $node)
            $valueStr .= '"' . $node->getTitle() . '" ';
        $valueStr .= "} ";

        Helper::log("build coop query, flags = " . $flags);
        $selectKey = self::buildCoopKeys($flags);

        if($flags & self::BUILD_GRAPH){
            $queryForm = self::constructCoopGraphStatement($selectKey);
        } else { //select query
            $selectKey['default'] = array(
                '?progLabel',
                '?progDesc',
                '?outcomeLabel',
                '?outcomeDesc',
            );

            $queryForm = "SELECT ";
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

        if($flags & self::OUTGOING_PROGRAMS && $flags & self::INCOMING_OUTCOMES){

            //union needed
            $whereStr =
                "WHERE { { " .
                    $valueStr .
                    self::whereCoopGraphStatement($selectKey['sendLab'],
                        $selectKey['recLabel']) .
                " } UNION { " .
                    $valueStr .
                    self::whereCoopGraphStatement($selectKey['sendLabUnion'],
                        $selectKey['recLabelUnion']) .
                " } }";

        } else {
            $whereStr =
                "WHERE { " .
                $valueStr .
                self::whereCoopGraphStatement($selectKey['sendLab'],
                    $selectKey['recLabel']) .
                " }";
        }

        //build query
        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            $queryForm . //SELECT|CONSTRUCT
            'FROM ' . SophiaGlobal::GRAPH_1 .
            $whereStr
        );

        return $q;
    }

    //        /*build select key regardless if it will be used or not
    //          (i.e. replaced by CONSTRUCT)*/
    private static function buildCoopKeys($flags){

        Helper::log("build coop query, flags = " . $flags);
        $selectKey = [];
        if($flags & self::OUTGOING_PROGRAMS && $flags & self::INCOMING_OUTCOMES){

            Helper::log("build keeps both");
            $selectKey['sendLab'] = '?entities';
            $selectKey['recLabel'] = '?ent2Label';
            /*---- Union ----*/
            $selectKey['sendLabUnion'] = '?ent1Label';
            $selectKey['recLabelUnion'] = '?entities';


        } else {
            Helper::log("build keys single");
            if ($flags & self::OUTGOING_PROGRAMS) {
                $selectKey['sendLab'] = '?entities';
            } else {
                $selectKey['sendLab'] = '?ent1Label';
            }

            if ($flags & self::INCOMING_OUTCOMES) {
                $selectKey['recLabel'] = '?entities';
            } else {
                $selectKey['recLabel'] = '?ent2Label';
            }
        }
        
        return $selectKey;
    }

    private static function constructCoopGraphStatement(array $selectKey){

        $retString =
            'CONSTRUCT {' .
            '?sendBody ns2:Grants ?prog. ' .
            '?prog ns2:Enables ?outcome. ' .
            '?outcome ns2:Empowers ?recBody. ' .
            '?prog rdfs:label ?progLabel. ' .
            '?outcome rdfs:label ?outcomeLabel. ' .
            '?prog ns2:Content ?progDesc. ' .
            '?outcome ns2:Content ?outcomeDesc. ' .
            '?sendBody rdf:type ns2:CommonwealthBody. ' .
            '?recBody rdf:type ns2:CommonwealthBody. ' .
            '?prog rdf:type ns2:Program. ' .
            '?outcome rdf:type ns2:Outcome. ';

        //if this key exists, we must be building a union graph
        if(array_key_exists('sendLabUnion', $selectKey))
            $retString .=
                '?sendBody rdfs:label ' . $selectKey['sendLabUnion']    . ". " .
                '?sendBody rdfs:label ' . $selectKey['sendLab']         . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabelUnion']   . ". ";
        else //Non-union graph
            $retString .=
                '?sendBody rdfs:label ' . $selectKey['sendLab']         . ". " .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". ";

        $retString .=  "} ";

        return $retString;
    }

    private static function whereCoopGraphStatement(string $sent, string $rec){

        return
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
            '?sendBody rdfs:label ' . $sent . ". " . //ent label
            '?prog rdfs:label ?progLabel. ' .       //program label
            '?outcome rdfs:label ?outcomeLabel. ' . //outcome (purpose) lab
            '?recBody rdfs:label ' . $rec . ". " . //rec body
            //Apply filters to constrain to classes
            '?sendBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .   //Filters: super and all subclasses
            '?prog a ns2:Program. ' .
            '?outcome a ns2:Outcome. ' .
            '?recBody a/rdfs:subClassOf* ns2:CommonwealthAgent. ' .
            '?auth a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth1 a/rdfs:subClassOf* ns2:Authority. ' .
            '?auth2 a/rdfs:subClassOf* ns2:Authority. ';
    }
}