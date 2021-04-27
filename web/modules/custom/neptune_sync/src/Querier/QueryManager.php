<?php 

namespace Drupal\neptune_sync\Querier;


use Drupal\neptune_sync\Utility\Helper;

/**
 * Class QueryManager
 * @package Drupal
 * @author Alexis Harper | DoF
 * A class for running SPAQRL Querier from drupal to neptune, leaving a file
 * for feeds to read and import the query results. all query calls should go
 * through this class
 */
class QueryManager
{
    protected $query;

    public function __construct(){
        QueryTemplate::init();
    }

    /**
     * @deprecated for NeptuneImporter
     * Creates json files based on SPARQL queries ready to import into
     * drupal via feeds
     */
    public function syncAllDatasets(){

        Helper::log('Sync all, syncing legsislations');
        $q = QueryTemplate::$queries['getLegislations'];
        $this->runQuery($q);

        Helper::log('legislation complete, syncing bodies');
        $q = QueryTemplate::$queries['getBodies'];
        $this->runQuery($q);

        Helper::log('bodies complete, syncing portfolios');
        $q = QueryTemplate::$queries['getPortfolios'];
        $this->runQuery($q);

        Helper::log('portfolios complete, syncing classes');
        $q = QueryTemplate::$queries['getClasses'];
        $this->runQuery($q);

        Helper::log('classes complete, syncing properties');
        $q = QueryTemplate::$queries['getProperties'];
        $this->runQuery($q);

        Helper::log('properties synced, sync complete');
    }

    /**
     * encodes c++/java string encodings out to be utf-8 compliant
     * @todo
     *      This is terribly done but i have nor the time and patients to fix right now. It works but will redo in the near future for a cleaner solution
     *
     *
     * @param $res
     * @return mixed
     */
    private function processResults($res){
        return $res;
        //return json_decode('"'.$res.'"');
    }

    /**
     * Separated from run query for encapsulation principles despite having similar
     * functionality
     * @param Query $query
     *      The query to execute
     */
    public function runCustomQuery($query){
        return $this->runQuery($query);
    }

    /**
     * Executes a SPARQL query
     * 2>&1 | tee  (https://www.php.net/manual/en/function.shell-exec.php) must
     * be used to clear sterr which will halt shell_exec if not placed
     * @param Query $query
     *      The SPARQL query to wrap the execute command around
     * @return string|null
     *      The queries result. Normally a graph in RDF form or a select result in json
     */
    protected function runQuery($query){
        $cmd = 'curl -s -X POST --data-binary \'query=' . $query->getQuery() . '\' '
                . $query->getDestination() . " 2>> neptune_sync_exec.log";
        Helper::log('Attempting to execute query: ' . $cmd);

        //run query
        $res = shell_exec($cmd);
        Helper::log('Query executed.');
        Helper::log('Result: ' . $res);

        //if output path exists
        if($query->getOutputPath() != null) {
            Helper::log("out to file");
            //write results
            $res_file = fopen($query->getOutputPath(), "w");
            Helper::log(self::processResults($res));
            fwrite($res_file, self::processResults($res));
            fclose($res_file);
        }

        return self::processResults($res);
    }
}

    
