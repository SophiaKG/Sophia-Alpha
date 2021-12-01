<?php


namespace Drupal\neptune_sync\Querier\Collections;


use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryTemplate;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\NodeInterface;

/**
 * Class NodeQuerier
 * @package Drupal\neptune_sync\Querier\Collections
 * @author Alexis Harper | DoF
 * This class is for building sparql queries that has (intended) generic functionality
 *  across node types (Bodies|Legislations|Portfolios)
 */
class NodeQuerier extends QueryBuilder {

    /**
     * @param $askClauseFunc string inserts of "part of" queries to run as an ASK
     *  query. See uses
     * @return Query The query ready to execute bound to (TRUE|FALSE)
     *
     * @uses getValidatedAuthorityPart()
     * @uses getStaffingPart()
     * @uses getEstablishedByRegulationPart()
     * @uses getExemptPart()
     * @uses getCorpNonCorpPart()
     * @uses getRegulatedCorpComEntity()
     */
    public static function buildAskQuery(string $askClauseFunc){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'ASK { ' .
            $askClauseFunc .
            ' }');
        return $q;
    }

    /**
     * Checks if a passed in node (Government Body) is part of a ns2 class
     *
     * @param NodeInterface $node the node to run the query for
     * @param $is_a string the NS2 class we are testing against
     * @return Query The query ready to execute bound to (TRUE|FALSE)
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
     * Sparql reference: https://aws-neptune-sophia-dev.notebook.ap-southeast-2.sagemaker.aws/notebooks/Neptune/Sophia%20Alpha%20QueryBuilder%20refrence.ipynb
     *
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_1
     * @param NodeInterface $node The node to get the portfolio for
     * @return Query The query ready to execute returning the assigned portfolio
     *  to $node bound to:
     *  ?port = UUID | ?portlabel = title
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
            //'?port ns2:live true. ' .  /* @TODO temp disabled as on a fresh scrape an AAO can be out of sync with the flipchart authority */
            '?flip ns2:witnesses ?auth. ' .
            '?flip a ns2:FinanceEntityList. ' .
            '?flip ns2:live true. ' .
            '} ORDER BY DESC(?datetime) ' .
            'LIMIT 1');

        return $q;
    }

    /**
     * Gets the legislation for a Government Body
     *
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_1
     * @param NodeInterface $node
     * @return Query a ready to execute query returning a list of enabling
     *  legislations for a body bound to
     *  ?legislation = UUID | ?legislationLabel = title
     */
    public static function getBodyLegislation(NodeInterface $node){

        $q = new Query(QueryTemplate::NEPTUNE_ENDPOINT);
        $q->setQuery(
            SophiaGlobal::PREFIX_ALL() .
            'SELECT DISTINCT ?legislation ?legislationLabel ' .
            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
            'WHERE { '.
            '?authority  ns2:bindsTo ' . self::getUri($node, 'ns2') . '. ' .
            '?authority  ns2:binds ?est. ' .
            '?est a ns2:Establishment. ' .
            '?leg ns2:grants ?authority. ' .
            '{ ' .
            '?leg ns2:hasSeries ?legislation. ' .
            '} UNION { ' .
            '?leg ns2:hasSeries ?leg2. ' .
            '?legislation ns2:hasSubordinate ?leg2. ' .
            '} UNION { ' .
            '?leg a ns2:Series. ' .
            'BIND (?leg as ?legislation)' .
            '} UNION { ' .
            '?leg a ns2:Series. ' .
            '?legislation ns2:hasSubordinate ?leg. ' .
            '} ' .
            '?legislation ns2:canonicalName  ?legislationLabel. ' .
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
     *
     * @uses \Drupal\neptune_sync\Utility\SophiaGlobal::GRAPH_0
     * @param NodeInterface $node currently only working for bodies
     * @return Query the complete query ready to execute
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

}