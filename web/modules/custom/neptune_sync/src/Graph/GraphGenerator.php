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

        $this->query = QueryBuilder::buildLocalGraph($this->name, $node->getTitle());

        $query_mgr = new QueryManager();
        $query_mgr->runCustomQuery($this->query);

        $this->buildGraph();
        return $this->formatGraph();
    }

    /**
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
        //kint(\EasyRdf_Format::getFormats());
       // Helper::var_dump(\EasyRdf_Format::getFormats(), "formats");
        $graph->parse($rdf, 'turtle');

        Helper::log("easy rdf graph dump");
        Helper::log($graph->dump());
        Helper::log("dump complete");
        //kint($graph->toRdfPhp());
        //kint($graph->resources());
        $reso = $graph->resources();

        $nodes = [];
        $edges = [];

        foreach($graph->resources() as $resource){

            $nodes[$resource->getUri()] = array('id'=>$resource->getUri(), 'label' => $resource->localName(), 'color' => '#1969c7', 'category' => $resource->type());


            foreach($resource->properties() as $edge){
                foreach($resource->allResources($edge) as $resource_b){  //for resources
                    if($resource_b->getUri() == "http://www.w3.org/2002/07/owl#Class")
                        continue;

                    $nodes[$resource_b->getUri()] = array('id'=>$resource_b->getUri(),
                                                            'label' => $resource_b->localName(),
                                                            'color' => '#1969c7',
                                                            'shape' => 'circle',
                                                            'category' => $resource_b->type()
                                                        );
                    $edges[$resource->getUri() . $resource_b->getUri()] = array(
                                                            'sourceID'=> $resource->getUri(),
                                                            'label' => $edge,
                                                            'targetID' => $resource_b->getUri()
                                                        );
                }
                foreach($resource->allLiterals($edge) as $literal){ //for labels
                    $nodes[$literal->getValue()] = array('id'=>$literal->getValue(),
                                                            'label' => $literal->getvalue(),
                                                            'color' => '#edbe13',
                                                            'shape' => 'rect',
                                                            'category' => 'rdfs:label'
                                                        );
                    $edges[$resource->getUri() . $literal->getValue()] = array(
                                                            'sourceID'=> $resource->getUri(),
                                                            'label' => $edge,
                                                            'targetID' => $literal->getValue());
                }
            }
            //add labels
        }

        $json = json_encode(array('nodes' => array_values($nodes), 'edges' => array_values($edges)));
        Helper::log($json);
        file_put_contents('../../filedump/graph.json', $json);


        /*$node = 'file:///home/andnfitz/GovernmentEntities.owl#CommonwealthBody';
        kint($reso[$node]->properties());
        kint($reso[$node]->types());
        kint($reso[$node]->label());
        kint($reso[$node]->shorten());
        kint($reso[$node]->localName());
        kint($reso[$node]->primaryTopic());

        $node = 'file:///home/andnfitz/GovernmentEntities.owl#ASPOFFSHORECOMPANYLIMITED-GLOBALOPPORTUNITIESSECONDARYFUNDII-A';*/
       /* kint($reso[$node]->properties());
        kint($reso[$node]->types());
        kint($reso[$node]->label());
        kint($reso[$node]->shorten());
        kint($reso[$node]->localName());
        kint($reso[$node]->primaryTopic());*/
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