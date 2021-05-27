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
    }

    /**
     * encodes c++/java string encodings out to be utf-8 compliant
     * @todo
     *      This is terribly done but i have nor the time and patients to fix right now. It works but will redo in the near future for a cleaner solution
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
     * @return string|null  (rdf|Json)|null
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
                . $query->getDestination() . " 2>> logs/neptune_sync_exec.log";
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

    
