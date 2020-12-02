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
            '?auth ns2:BindsTo ' . $node . '. ' .
            '?auth ns2:Binds ' . $term . '. ' .
            '?flipchart ns2:Witnesses ?auth. ' .
            '?flipchart ns2:live true. ';
    }

    public static function getStaffingPart($node, $term){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?ent ns2:live true. ' .
            '?auth0 ns2:Binds ?myStaffing. ' .
            '?auth0 ns2:BindsTo ' . $node . '. ' .
            '?auth1 ns2:Binds ' . $term . '. ' .
            '?auth1 ns2:isRestrictionOf ?myStaffing. ';
    }

    /** specific for R key
     * @param $node
     * @return string */
    public static function getRegulatedCorpComEntity($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?auth ns2:BindsTo ' . $node . '. ' .
            '?auth ns2:Binds ?est. ' .
            '?est a ns2:Establishment. ' .
            '?leg ns2:Grants ?auth. ' .
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
            '?auth ns2:Binds ' . $node . '. ' .
            '?auth ns2:hasExemptionFrom ?section. ' .
            '?section rdfs:label "22". ' .
            '?thing ns2:Grants ?auth. ' .
            '?thing ns2:SubsectionOf ?leg2. ' .
            '?leg2 ns2:live true. ' .
            '?section ns2:SubsectionOf ?leg. ' .
            '?leg ns2:hasSeries ns2:C2013A00123. ';
    }


    /** specific to # key
     * @param $node
     * @param $term
     * @return string
     */
    public static function getStaffingWithLegislationPart($node, $term){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            self::getStaffingPart($node, $term) .
            '?auth2 ns2:Binds ?otherTerm. ' .
            '?auth2 ns2:isRestrictionOf ?myStaffing. ' .
            '?leg a ns2:Legislation. ' .
            '?leg ns2:live true. ' .
            '?leg ns2:Grants ?auth2. ' .
            '?leg ns2:Grants ?auth3. ' .
            '?auth3 ns2:Binds ?est. ' .
            '?auth3 ns2:BindsTo ' . $node . '. ' .
            '?est a ns2:Establishment. ';
    }

    public static function getEstablishedByRegulationPart($node){
        if($node instanceof NodeInterface)
            $node = QueryBuilder::getUri($node, "ns2");
        return
            '?procAuth ns2:Binds ?proc. ' .
            '?procAuth ns2:BindsTo ' . $node . '. ' .
            '?proc a ns2:Procurement. ' .
            '?cprauth ns2:isRestrictionOf ?proc. ' .
            '?cprauth ns2:Binds ns2:F2014L00911CommonwealthProcurementRules. ' .
            '?leg ns2:Grants ?cprauth. ' .
            '?leg ns2:live true. ';
    }
}