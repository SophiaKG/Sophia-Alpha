<?php
namespace Drupal\neptune_sync\Querier\Collections;

use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\node\NodeInterface;

/**
 * Class SummaryViewQuerier
 * Author: AlexHarp|DoF
 *
 * A class for building queries that allocate summary view keys.
 * Functions are made as generic as possible
 */
class SummaryViewQuerier {

    /** Checks if an object has a property via authority that is validated (Witness)
     * by some means, normally the flipchart
     *
     * @param $node
     * @param $term string The property to check
     * @return string a part of query to be inserted into a where clause */
    public static function getValidatedAuthorityPart($node, $term){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?auth ns2:bindsTo ' . $node . '. ' .
            '?auth ns2:binds ' . $term . '. ' .
            '?flipchart ns2:witnesses ?auth. ' .
            '?flipchart ns2:live true. ';
    }

    /** Determines PS act key
     * @param $node
     * @return string returns a part-of query that determines if a node is bound
     * by the ps act
     */
    public static function getPsActPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?workAuth ns2:binds ?staff. ' .
            '?workAuth ns2:worksFor ' . $node . '. ' .
            '?termAuth ns2:binds ?term. ' . 
            '?termAuth ns2:bindsTo ?staff. ' .
            'ns2:C2004A00538 ns2:defines ?term. ';
    }

    /** specific to # key
     * @param $node
     * @return string
     */
    public static function getStaffingWithLegislationPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            self::getPsActPart($node) .
            'FILTER EXISTS{ ' .
                '?workAuth1 ns2:binds ?staff1. ' .
                '?workAuth1 ns2:worksFor ' . $node . '. ' .
                'MINUS{ '.
                    '?termAuth1 ns2:binds ?term1. ' .
                    '?termAuth1 ns2:bindsTo ?staff1. ' .
                    'ns2:C2004A00538 ns2:defines ?term1. ' .
                '} ' .
            '} ';

        /* '?auth2 ns2:binds ?otherTerm. ' .
         '?auth2 ns2:isRestrictionOf ?myStaffing. ' .
         '?leg a ns2:legislation. ' .
         '?leg ns2:live true. ' .
         '?leg ns2:grants ?auth2. ' .
         '?leg ns2:grants ?auth3. ' .
         '?auth3 ns2:binds ?est. ' .
         '?auth3 ns2:bindsTo ' . $node . '. ' .
         '?est a ns2:Establishment. ';*/
    }

    /** Specific to ▲ key
     * @param $node
     * @return string
     */
    public static function getParliamentaryActPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?ent ns2:live true. ' .
            '?auth0 ns2:binds ?myStaffing. ' .
            '?auth0 ns2:bindsTo ' . $node . '. ' .
            '?auth1 ns2:binds ns2:C2004A00536ParliamentaryServiceemployment. ' .
            '?auth1 ns2:isRestrictionOf ?myStaffing. ';
    }

    /** specific for R key
     * @param $node
     * @return string */
    public static function getRegulatedCorpComEntity($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?auth ns2:bindsTo ' . $node . '. ' .
            '?auth ns2:binds ?est. ' .
            '?est a ns2:Establishment. ' .
            '?leg ns2:grants ?auth. ' .
            '?leg ns2:live true. ' .
            '?leg ns2:hasSeries ?srs. ' .
            '?superSrs ns2:hasSubordinate ?srs.';
    }

    /** specific for X key
     * @param $node
     * @return string */
    public static function getExemptPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?auth ns2:binds ' . $node . '. ' .
            '?auth ns2:hasExemptionFrom ?section. ' .
            '?section rdfs:label "22". ' .
            '?thing ns2:grants ?auth. ' .
            '?thing ns2:subsectionOf ?leg2. ' .
            '?leg2 ns2:live true. ' .
            '?section ns2:subsectionOf ?leg. ' .
            '?leg ns2:hasSeries ns2:C2013A00123. ';
    }

    /** specific for ℗ key
     * @param $node
     * @return string
     */
    public static function getEstablishedByRegulationPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?procAuth ns2:binds ?proc. ' .
            '?procAuth ns2:bindsTo ' . $node . '. ' .
            '?proc a ns2:Procurement. ' .
            '?cprauth ns2:isRestrictionOf ?proc. ' .
            '?cprauth ns2:binds ns2:F2014L00911CommonwealthProcurementRules. ' .
            '?leg ns2:grants ?cprauth. ' .
            '?leg ns2:live true. ';
    }

    public static function getCorpNonCorpPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?auth ns2:bindsTo '  . $node . '. ' .
            '?auth ns2:binds ns2:C2004A00818bodycorporate. '.
            '?leg ns2:grants ?auth. ' .
            '?leg ns2:live true. '.
            '?auth1 ns2:bindsTo ' . $node . '. ' .
            '?auth1 ns2:binds ns2:C2013A00123noncorporateCommonwealthentity. ' .
            '?flip ns2:witnesses ?auth1. ' .
            '?flip ns2:live true. ';
    }
}
