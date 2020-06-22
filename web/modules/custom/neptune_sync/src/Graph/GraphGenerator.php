<?php

namespace Drupal\neptune_sync\Graph;


use Drupal\neptune_sync\querier\QueryBuilder;
use Drupal\neptune_sync\querier\QueryManager;
use Drupal\neptune_sync\Utility\Helper;
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

    /**@TODO edit this
     * Builds a local graph to (currently) one step from the passed in node.
     * Logic: hash-name -> build query -> execute query -> build graph structure
     *          -> visualize graph
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the file path of the visual graph constructed
     */
    public function buildGraphFromNode(NodeInterface $node){

        $this->query = QueryBuilder::buildCustomLocalGraph($node);
        $query_mgr = new QueryManager();
        //$query_mgr->runCustomQuery($this->query);

        $xxx = $this->rdfToGraph($query_mgr->runCustomQuery($this->query));
        Helper::log("xxx = " . $xxx);

        return $xxx;
    }

    /**
     * @deprecated by echarts
     * Builds a local graph with the ability to modify certain aspects of the build via
     * passed in filters
     * @param array $filters
     *      filters to add details to how the graph is built, mapped to struct in
     *      GraphFilters constructor
     * @return string
     *      returns the server filepath of the graph generated
     */
    public function buildGraphFromFilters(array $filters){
        try {
            $this->name = bin2hex(random_bytes(5));
        } catch (\Exception $e) { }

        $filters = new GraphFilters($filters);
        $this->query = QueryBuilder::buildCustomLocalGraph($this->name, $filters);

        $query_mgr = new QueryManager();
        $graph_rdf = $query_mgr->runCustomQuery($this->query);

        $this->rdfToGraph($graph_rdf);
        /*$this->buildGraph();
        return $this->formatGraph();*/
    }

    private function rdfToGraph($rdf){
        $graph = new \EasyRdf_Graph(null, $rdf, 'turtle');
        $graph->parse($rdf, 'turtle');

        $nodes = [];
        $edges = [];
        $cat = [];

        foreach($graph->resources() as $resource) {

            //add root node
            $nodes[$resource->getUri()] = $this->buildNode($resource);

            //add its direct edges and their nodes
            foreach ($resource->properties() as $edge) {
                //resources and literals
                foreach ($resource->all($edge) as $resource_b) {

                    $nodes[$this->getID($resource_b)] = $this->buildNode($resource_b);
                    $edges[$this->getID($resource) . $this->getID($resource_b)] =
                        $this->buildEdge($resource, $edge, $resource_b);

                    //add the type of both nodes to the distinct category set
                    $cat[$this->getType($resource)] =
                        array('name' => $this->getType($resource));
                    $cat[$this->getType($resource_b)] =
                        array('name' => $this->getType($resource_b));
                }
            }
        }
        $json = json_encode(array('category' => array_values($cat),
                                    'nodes' => array_values($nodes),
                                    'edges' => array_values($edges)));

        //kint($json);
        Helper::log($json);

        return $json;
    }

    private function buildNode($resource){

        //don't add Owl:class
        if($this->getID($resource) == "http://www.w3.org/2002/07/owl#Class")
            return array();

        //If the node is a label
        if(is_a($resource, 'EasyRdf_Literal')){
            return array('id'=>$resource->getValue(),
                'label' => $resource->getvalue(),
                /*'color' => '#edbe13',*/
                'shape' => 'rect',
                'category' => $this->getType($resource)
            );
        } //if the node is a resource
        else if(is_a($resource, 'EasyRdf_Resource')) {
            return array('id' => $resource->getUri(),
                'label' => $resource->localName(),
                /*'color' => '#1969c7',*/
                'shape' => 'circle',
                'category' => $this->getType($resource)
            );
        }
    }

    private function buildEdge($a, $edge, $b){
        return array(
            'sourceID'=> $this->getID($a),
            'label' => $edge,
            'targetID' => $this->getID($b));
    }

    private function getID($resource){
        if(is_a($resource, 'EasyRdf_Literal'))
            return $resource->getValue();
        elseif(is_a($resource, 'EasyRdf_Resource'))
            return $resource->getUri();
    }

    private function getType($resource){

        $type = '';
        if (is_a($resource, 'EasyRdf_Literal')) {
            $type = 'rdfs:label';
        } else if ($resource->type() != null) {
            $type = $resource->type();
        } else {
            $type = 'misc';
        }

        return $type;
    }

    /**
     * Builds a graph from RDF/ttl syntax
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
        \drupal::logger('neptune_sync')->notice('Graph ' . $this->name .
            ' created. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);
    }

    /**
     * Formats the graph into a spatial setting for human visualization
     * //arg: dot -Tsvg -o [OUTPUT] [INPUT]
     * //arg: dot -T[FILETYPE] -o [NAME].svg [NAME].dot
     */
    private function formatGraph(){

        $cmd = 'dot -T' . self::GRAPH_FILETYPE . ' -o sites/default/files/graphs/'
            . $this->name . '.' . self::GRAPH_FILETYPE . ' ' . self::MODULE_RESOURCE_DIR
            . 'dot/' . $this->name . '.dot 2>&1';
        $res = shell_exec($cmd);

        //log
        \drupal::logger('neptune_sync')->notice('Graph ' . $this->name .
            ' converted to svg. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);

        return '/sites/default/files/graphs/' . $this->name . '.' . self::GRAPH_FILETYPE;
    }
}