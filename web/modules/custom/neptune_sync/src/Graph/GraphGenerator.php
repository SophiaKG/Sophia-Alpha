<?php

namespace Drupal\neptune_sync\Graph;


use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\neptune_sync\Querier\Collections\CoopGraphQuerier;
use Drupal\neptune_sync\Querier\Query;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\NodeInterface;
use EasyRdf\RdfNamespace;
use EasyRdf_Literal;
use EasyRdf_Resource;

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
    /** @deprecated */
    const GRAPH_VISUALIZER_PATH = 'ontology-visualization/' .
                                    'ontology_viz.py';

    //protected $name;
    protected $query; //The query we are using to generate the graph from

    public function __construct(){

        //Add our custom name spaces to EasyRDF
        \EasyRdf_Namespace::set(SophiaGlobal::IRI['ns1']['name'], SophiaGlobal::IRI['ns1']['loc']);
        \EasyRdf_Namespace::set(SophiaGlobal::IRI['ns2']['name'], SophiaGlobal::IRI['ns2']['loc']);
    }

    /**
     * Builds a graph around a node with k =  2 expansion
     *  -build query
     *  -run query
     *  -load returned RDF into easy_RDF
     *  -build nodes and edges through easy_RDF
     *  -convert node and edge set into json
     *  -return
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the json constructed from rdfToGraph
     */
    public function buildGraphFromNode(NodeInterface $node){

        $this->query = QueryBuilder::buildCustomLocalGraph($node);

        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    public function buildCoopGraphFromNode(NodeInterface $node){

        $this->query =  CoopGraphQuerier::getCooperativeRelationships(array($node),
            (CoopGraphQuerier::BUILD_GRAPH | CoopGraphQuerier::OUTGOING_PROGRAMS ));

        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    /**
     * @deprecated temp function for ssc, shows incoming and outgoing
     * @param NodeInterface $node
     * @return string
     * @throws \EasyRdf_Exception
     */
    public function buildCoopGraphAllFromNode(NodeInterface $node){
        $this->query = CoopGraphQuerier::getCooperativeRelationships(array($node),
            (CoopGraphQuerier::BUILD_GRAPH |
                CoopGraphQuerier::OUTGOING_PROGRAMS |
                CoopGraphQuerier::INCOMING_OUTCOMES ));

        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    public function buildCoopGraphIntersect(array $ids){

        try {
            $nodes = \Drupal::entityTypeManager()->getStorage(SophiaGlobal::NODE)
                ->loadMultiple($ids);
        } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
            Helper::log("Err506-1: Unable to loads Nodes from Id array while building
             graph. Id array = ", true, $ids);
        }

        $this->query = CoopGraphQuerier::getCooperativeRelationships($nodes,
            (CoopGraphQuerier::BUILD_GRAPH |  CoopGraphQuerier::OUTGOING_PROGRAMS));

        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    private function parseGraph(Query $query, bool $easyRead){

        $query_mgr = new QueryManager();
        $rdf = $query_mgr->runCustomQuery($this->query);

        $rdfGraph = new \EasyRdf_Graph(null, $rdf, 'turtle');

        Helper::log("attempting to parse into easyRDF");

        try {
            $rdfGraph->parse($rdf, 'turtle');
        } catch (\EasyRdf_Exception $e) {
            Helper::log("Failed to parse RDF. Exception:\n\t\t" . $e .
                "\nRDF:\n\t\t" . $rdf);
        }

        Helper::log("parse complete");
        Helper::file_dump('easyrdf.html', $rdfGraph->dump('html'));

        return new GraphBuilder($rdfGraph, $easyRead);
    }

    /**
     * Converts a sparql construct query to json
     *      "category": [],
     *      "nodes": [
     *         "id":,
     *         "label":,
     *         "shape":,
     *         "category":
     *      ],
     *      "edges": [
     *          "sourceID":,
     *          "label":,
     *          "targetID"
     *      ]
     * @param $rdf string return of a SPARQL construct query for the local graph
     * @return string json of the local graph outputting [categories, nodes, edges]
     */
    private function rdfToGraph(GraphBuilder $graph){


        /** Go through each resource (not literals) as $resource
         *      Finds each type of their edges & finds each edge of that type as $edgeTypeName
         *          For each edge, get the values (resource or literal) it joins as $resource_b */
        foreach($graph->easyRdfGraph->resources() as $resource) {

            //add root node - Use local name to merge nodes from ns1 & ns2
            /** TODO can this be done better?*/
            $addNode = $graph->buildNode($resource);
            if($addNode) {
                $nodes[$graph->getID($resource)] = $addNode;
                $cat[$graph->getType($resource)] =
                    array('name' => $graph->getType($resource));
            }

            //add its direct edges and their nodes
            Helper::log("Resource " . $resource->getUri(). " contains properties: ");
            Helper::log($resource->properties());

            foreach ($resource->properties() as $edgeTypeName) {

                Helper::log("\tResource: " . $resource->getUri() . " In edge name:  " . $edgeTypeName);
                Helper::log("\tShortened = " . $resource->shorten());

                //resources and literals
                foreach ($resource->all($edgeTypeName) as $resource_b) {

                    Helper::log("\t\tResource: " . $resource->getUri() . " In edge name:  " . $edgeTypeName . " connecting to node: " . $resource_b->__toString());
                    /** TODO can this be done better?*/
                    $addNode = $graph->buildNode($resource_b);
                    if ($addNode) {
                        $nodes[$graph->getID($resource_b)] = $addNode;
                        $cat[$graph->getType($resource_b)] =
                            array('name' => $graph->getType($resource_b));
                    }

                    $edges[$graph->getID($resource) . $graph->getID($resource_b)] =
                        $graph->buildEdge($resource, $edgeTypeName, $resource_b);

                    //add the type of both nodes to the distinct category set


                }
            }
        }
        return $graph->getJsonGraph();
    }

    /**
     * Builds a graph from RDF/ttl syntax
     * Input is pulled from the queries output parameter XXX(can we pass this value through?)
     * arg: ontology_viz.py -o [OUTPUT] [INPUT] -O [ONTOLOGY|OPTIONAL]
     * arg: ontology_viz.py -o [NAME].dot [name].ttl
     * @deprecated Replaced by eCharts
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
     * @deprecated Replaced by eCharts
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

    /**
     * @deprecated by eCharts
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
}