<?php


namespace Drupal\neptune_sync\Querier\Collections;

use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;

class CoopGraphQuerier{
    
    const MULTIPLE_NODES    = 0x1;
    const OUTGOING_PROGRAMS = 0x2;
    const INCOMING_OUTCOMES = 0x4;
    const BUILD_GRAPH       = 0x8;

    public static function getCooperativeRelationships(array $nodes, $flags){

        //add values segment
        $valueStr = 'values ?entities {';
        foreach ($nodes as $node)
            $valueStr .= '"' . $node->getTitle() . '" ';
        $valueStr .= '}';
        
        $selectKey = self::buildCoopKeys($flags);

        $queryForm = "";
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
            $queryForm .= "\n";
        }

    }

    //        /*build select key regardless if it will be used or not
    //          (i.e. replaced by CONSTRUCT)*/
    private static function buildCoopKeys($flags){

        $selectKey = [];
        if($flags & self::OUTGOING_PROGRAMS & self::INCOMING_OUTCOMES){

            $selectKey['sendLab'] = '?entities';
            $selectKey['recLabel'] = '?ent2Label';
            /*---- Union ----*/
            $selectKey['sendLabUnion'] = '?ent1Label';
            $selectKey['recLabelUnion'] = '?entities';


        } else {
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
                '?sendBody rdfs:label ' . $selectKey['sendLabUnion']    . ". \n" .
                '?sendBody rdfs:label ' . $selectKey['sendLab']         . ". \n" .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". \n" .
                '?recBody rdfs:label ' .  $selectKey['recLabelUnion']   . ". \n";
        else //Non-union graph
            $retString .=
                '?sendBody rdfs:label ' . $selectKey['sendLab']         . ". \n" .
                '?recBody rdfs:label ' .  $selectKey['recLabel']        . ". \n";

        $retString .=  "} \n";

        return $retString;
    }
}