<?php

namespace Drupal\neptune_sync\querier;

/**
 * Class query_templates
 * @package Drupal\neptune_sync\querier
 * @author: Alexis Harper | DoF
 * This class is a template class for storing hard coded sparql queries to be
 * loaded into a static public variable for execution anywhere. All actual queries
 * should be hardcoded here and pulled into the queriy manager though the query
 * manager.
 */
class QueryTemplate
{
    /* Post init
     * @return
     *    ['getLabels']
     *    ['getLegislations']
     *    ['getBodies']
     */
    public static $queries = array();
    const NEPTUNE_ENDPOINT = 'https://sophia-neptune.cbkhatvpiwzj' .
                        '.ap-southeast-2.neptune.amazonaws.com:8182/sparql';
    const FEEDS_IMPORT_DIR = 'sites/default/files/feeds/';

    public static function init()
    {
        self::$queries['getLabels'] = self::getLabels();
        self::$queries['getLegislations'] = self::getLegislations();
        self::$queries['getBodies'] = self::getBodies();
    }

    private static function getLabels()
    {
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'labels/labels.json');
        $q->setQuery('SELECT DISTINCT ?subject ?predicate ?object ' .
                            'WHERE { ?subject ?predicate ?object . }');
        return $q;
    }

    private static function getLegislations()
    {
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'legislation/legislation.json');
        $q->setQuery('PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#> ' .
                            'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> ' .
                            'SELECT ?object ' .
                            'WHERE {?Entity a ns1:Legislation . ' .
                            '?Entity rdfs:label ?object . }');
        return $q;
    }
    private static function getBodies()
    {
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'bodies/bodies.json');
        $q->setQuery('PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#> ' .
                            'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> ' .
                            'SELECT ?object ' .
                            'WHERE {?Entity a ns1:CommonwealthBody . ' .
                                   '?Entity rdfs:label ?object .}');
        return $q;
    }
}