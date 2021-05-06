<?php

namespace Drupal\neptune_sync\Querier;

use Drupal\neptune_sync\Utility\SophiaGlobal;

/**
 * Class query_templates
 * @package Drupal\neptune_sync\Querier
 * @author: Alexis Harper | DoF
 * This class is a template class for storing hard coded sparql queries to be
 * loaded into a static public variable for execution anywhere. All actual queries
 * should be hardcoded here and pulled into the queries manager though the query
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


    public static function init(){
        self::$queries['getLabels'] = self::getLabels();
        self::$queries['getLegislations'] = self::getLegislations();
        self::$queries['getBodies'] = self::getBodies();
        self::$queries['getPortfolios'] = self::getPortfolios();
        self::$queries['getClasses'] = self::getClasses();
        self::$queries['getProperties'] = self::getProperties();
    }

    /**
     * @deprecated
     * @return Query
     */
    private static function getLabels(){
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'labels/labels.json');
        $q->setQuery('SELECT DISTINCT ?subject ?predicate ?object ' .
                            'WHERE { ?subject ?predicate ?object . }');
        return $q;
    }

    public static function getLegislations(){
        $q = new Query(self::NEPTUNE_ENDPOINT);
        $q->setQuery(SophiaGlobal::PREFIX_ALL() .
                            'SELECT DISTINCT ?obj ?objLabel ' .
                            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
                            'WHERE { ' .
                                '?obj a ns2:Series. ' .
                                '?obj rdfs:label ?objLabel. ' .
                            '}');
        return $q;
    }

    public static function getBodies(){
        $q = new Query(self::NEPTUNE_ENDPOINT);
        $q->setQuery(SophiaGlobal::PREFIX_ALL() .
                            'SELECT DISTINCT ?obj ?objLabel ' .
                            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
                            'WHERE { ' .
                                'VALUES ?type {ns2:CommonwealthBody ns2:LeadBody} ' .
                                //'?obj ns2:live true. ' .
                                '?obj a ?type. ' .
                                '?obj ns2:canonicalName ?objLabel. ' .
                            '}');
        return $q;
    }

    public static function getPortfolios(){
        $q = new Query(self::NEPTUNE_ENDPOINT);
        $q->setQuery(SophiaGlobal::PREFIX_ALL() .
                            'SELECT DISTINCT ?obj ?objLabel ' .
                            'FROM ' . SophiaGlobal::GRAPH_1 . ' ' .
                            'WHERE { ' .
                            /*    '?obj ns2:live true. ' .
                                '?obj a ns2:Portfolio. ' .
                                '?obj ns2:canonicalName ?objLabel. ' .*/
                            /* @TODO temp disabled as on a freash scrape an AAO can be out of sync with the flipchart authority */
                                '?obj a ns2:Portfolio. ' .
                                '?obj ns2:canonicalName ?objLabel. ' .
                                '?auth ns2:fallsUnder ?obj. ' .
                                '?flip ns2:witnesses ?auth. ' .
                                '?flip a ns2:FinanceEntityList. ' .
                                '?flip ns2:live true. ' .
                            '}');
        return $q;
    }

    private static function getClasses(){
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'classes/classes.json');
        $q->setQuery(SophiaGlobal::PREFIX_ALL() .
                            'SELECT DISTINCT ?object ' .
                            'WHERE {?Entity a owl:Class . ' .
                            '?Entity rdfs:label ?object .}');
        return $q;
    }

    private static function getProperties(){
        $q = new Query(self::NEPTUNE_ENDPOINT, self::FEEDS_IMPORT_DIR . 'properties/properties.json');
        $q->setQuery(SophiaGlobal::PREFIX_ALL() .
                            'SELECT DISTINCT ?object ' .
                            'WHERE {?Entity a owl:ObjectProperty . ' .
                            '?Entity rdfs:label ?object .}');
        return $q;
    }


}
