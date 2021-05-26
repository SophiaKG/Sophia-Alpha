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
 * A manager class for constructing a graph from passed in data
 */
class GraphGenerator
{
    protected $query; //The query we are using to generate the graph from

    public function __construct(){
        //Add our custom name spaces to EasyRDF
        \EasyRdf_Namespace::set(SophiaGlobal::IRI['ns1']['name'], SophiaGlobal::IRI['ns1']['loc']);
        \EasyRdf_Namespace::set(SophiaGlobal::IRI['ns2']['name'], SophiaGlobal::IRI['ns2']['loc']);
    }

    /**
     * Builds a graph around a node showing its attributes within k =  2
     *  expansion along the graph
     *  -build query
     *  -run query
     *  -load returned RDF into easy_RDF
     *  -build nodes and edges through easy_RDF
     *  -convert node and edge set into json with themed values
     *  -return
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the json constructed from rdfToGraph
     */
    public function buildGraphFromNode(NodeInterface $node){
        $this->query = LocalGraphQuerier::buildCustomLocalGraph($node);
        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    /**
     * Builds a graph around a node showing its outgoing cooperative relationships
     *  -build query
     *  -run query
     *  -load returned RDF into easy_RDF
     *  -build nodes and edges through easy_RDF
     *  -convert node and edge set into json with themed values
     *  -return
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the json constructed from rdfToGraph
     */
    public function buildCoopGraphFromNode(NodeInterface $node){
        $this->query =  CoopGraphQuerier::getCooperativeRelationships(array($node),
            (CoopGraphQuerier::BUILD_GRAPH | CoopGraphQuerier::OUTGOING_PROGRAMS ));
        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    /**
     * Builds a graph around a node showing its outgoing and incoming cooperative
     *  relationships
     *  -build query
     *  -run query
     *  -load returned RDF into easy_RDF
     *  -build nodes and edges through easy_RDF
     *  -convert node and edge set into json with themed values
     *  -return
     * @param NodeInterface $node
     *      The node that is the origin of the graph to be built
     * @return string
     *      the json constructed from rdfToGraph
     */
    public function buildCoopGraphAllFromNode(NodeInterface $node){
        $this->query = CoopGraphQuerier::getCooperativeRelationships(array($node),
            (CoopGraphQuerier::BUILD_GRAPH |
                CoopGraphQuerier::OUTGOING_PROGRAMS |
                CoopGraphQuerier::INCOMING_OUTCOMES ));

        return $this->rdfToGraph($this->parseGraph($this->query, true));
    }

    /**
     * Builds a unionised cooperative graph (outgoing) graph based on the id's passed in.
     *  cooperative relationships
     *  -build query
     *  -run query
     *  -load returned RDF into easy_RDF
     *  -build nodes and edges through easy_RDF
     *  -convert node and edge set into json with themed values
     *  -return
     * @param array $ids
     *      The Nid's of bodies to union in a coop graph.
     * @return string
     *       the json constructed from rdfToGraph.
     */
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

    /**
     * Loads the results of a SparQL construct query into an EasyRDF Graph structure
     * @param Query $query The query (construct) to execute and build into an EasyRDF graph
     * @param bool $easyRead if adjustments should be made to the structure of
     *  the graph to make it more readable (ie, remove RDF and ontology terminology)
     * @return GraphBuilder query converted to EasyRDF and wrapped in a class
     *  for easy manipulation
     */
    private function parseGraph(Query $query, bool $easyRead){

        $query_mgr = new QueryManager();
        $rdf = $query_mgr->runCustomQuery($this->query);

        $rdfGraph = new \EasyRdf_Graph(null, $rdf, 'turtle');

        Helper::log("attempting to parse into easyRDF");

        try {
            $rdfGraph->parse($rdf, 'turtle');
        } catch (\EasyRdf_Exception $e) {
            Helper::log("Err507 - Failed to parse RDF. Exception:\n\t\t" . $e .
                "\nRDF:\n\t\t" . $rdf);
        }

        Helper::log("parse complete");
        Helper::file_dump('easyrdf.html', $rdfGraph->dump('html'));

        return new GraphBuilder($rdfGraph, $easyRead);
    }

    /**
     * Builds a eChart json based on easyRDF resources
     * eChart json format in the form of:
     *      "category": [],
     *      "nodes": [
     *         "id":,
     *         "label":,
     *         "value":,
     *         "shape":,
     *         "symbolSize":,
     *         "category":
     *      ],
     *      "edges": [
     *          "sourceID":,
     *          "label":,
     *          "targetID"
     *      ]
     * @param GraphBuilder $graph A blank initialised graph to build eChart json from
     * @return string json of the local graph outputting [categories, nodes, edges]
     */
    private function rdfToGraph(GraphBuilder $graph){

        /** Go through each resource (not literals) as $resource
         *      Finds each type of their edges & finds each edge of that type as $edgeTypeName
         *          For each edge, get the values (resource or literal) it joins as $resource_b */
        foreach($graph->easyRdfGraph->resources() as $resource) {
            Helper::log("Resource " . $resource->getUri(). " contains properties: ");
            Helper::log($resource->properties());
            $graph->buildNode($resource);

            /** ITR edge type **/
            foreach ($resource->properties() as $edgeTypeName) {
                Helper::log("\tResource: " . $resource->getUri() . " In edge name:  " . $edgeTypeName);
                Helper::log("\tShortened = " . $resource->shorten());

                /** ITR nodes connect via edge type */
                foreach ($resource->all($edgeTypeName) as $resource_b) {
                    Helper::log("\t\tResource: " . $resource->getUri() . " In edge name:  " . $edgeTypeName . " connecting to node: " . $resource_b->__toString());
                    $graph->buildNode($resource_b);
                    $graph->buildEdge($resource, $edgeTypeName, $resource_b);
                }
            }
        }

        return $graph->getJsonGraph();
    }
}