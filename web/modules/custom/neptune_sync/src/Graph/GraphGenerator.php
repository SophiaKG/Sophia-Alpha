<?php

namespace Drupal\neptune_sync\Graph;


use Drupal\neptune_sync\querier\QueryBuilder;
use Drupal\neptune_sync\querier\QueryManager;
use Drupal\node\NodeInterface;

/**
 * Class GraphGenerator
 * @package Drupal\neptune_sync\Graph
 * @author Alexis Harper | DoF
 * A manager class for constructing a graph from passed in information
 */
class GraphGenerator
{
    const GRAPH_FILETYPE = 'svg';
    const MODULE_RESOURCE_DIR = 'modules/custom/neptune_sync/resources/';
    const GRAPH_VISUALIZER_PATH = 'ontology-visualization/' .
                                    'ontology_viz.py';

    protected $name;
    protected $query;

    /**
     * Builds a local graph to (currently) one step from the passed in node.
     * Logic: hash-name -> build query -> execute query -> build graph structure
     *          -> visualize graph
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the file path of the visual graph constructed
     */
    public function buildGraphFromNode(NodeInterface $node){

        try {
            $this->name = bin2hex(random_bytes(5));
        } catch (\Exception $e) { }

        //$this->query = QueryBuilder::buildLocalGraph($this->name, $node->getTitle());
        $filters = new GraphFilters(null);
        $filters->steps = 2;
        $this->query = QueryBuilder::buildCustomLocalGraph($this->name, $node->getTitle(), $filters);


        $query_mgr = new QueryManager();
        $query_mgr->runCustomQuery($this->query);

        $this->buildGraph();
        return $this->formatGraph();
    }

    public function buildGraphFromFilters($filters){
        try {
            $this->name = bin2hex(random_bytes(5));
        } catch (\Exception $e) { }

        $filters = new GraphFilters($filters);
        $this->query = QueryBuilder::buildCustomLocalGraph($this->name, $filters);

        $query_mgr = new QueryManager();
        $query_mgr->runCustomQuery($this->query);

        $this->buildGraph();
        return $this->formatGraph();
    }

    /**
     * Input is pulled from the queries output parameter XXX(can we pass this value through?)
     * arg: ontology_viz.py -o [OUTPUT] [INPUT] -O [ONTOLOGY|OPTIONAL]
     * arg: ontology_viz.py -o [NAME].dot [name].ttl
     */
    private function buildGraph(){

        $cmd = 'python3 ' . self::GRAPH_VISUALIZER_PATH . ' -o ' . self::MODULE_RESOURCE_DIR
            . 'dot/' . $this->name . '.dot ' . QueryBuilder::GRAPH_WORKING_DIR .
            $this->name . '.rdf 2>&1';
        $res = shell_exec($cmd);

        //log
        \drupal::logger('neptune_sync') ->notice('Graph ' . $this->name .
            ' created. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);
    }

    /**
     * //arg: dot -Tsvg -o [OUTPUT] [INPUT]
     * //arg: dot -T[FILETYPE] -o [NAME].svg [NAME].dot
     */
    private function formatGraph(){

        $cmd = 'dot -T' . self::GRAPH_FILETYPE . ' -o sites/default/files/graphs/'
            . $this->name . '.' . self::GRAPH_FILETYPE . ' ' . self::MODULE_RESOURCE_DIR
            . 'dot/' . $this->name . '.dot 2>&1';
        $res = shell_exec($cmd);

        //log
        \drupal::logger('neptune_sync') ->notice('Graph ' . $this->name .
            ' converted to svg. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);

        return '/sites/default/files/graphs/' . $this->name . '.' . self::GRAPH_FILETYPE;
    }
}