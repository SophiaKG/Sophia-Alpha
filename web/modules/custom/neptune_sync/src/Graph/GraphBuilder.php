<?php


namespace Drupal\neptune_sync\Graph;


use Drupal\neptune_sync\Utility\Helper;
use EasyRdf_Literal;
use EasyRdf_Resource;

/**
 * Class GraphBuilder
 * A wrapper class for using EasyRdf to build an eCharts array
 * @package Drupal\neptune_sync\Graph
 *
 * @author AlexHarp|DoF
 */
class GraphBuilder
{

    public $easyRdfGraph;

    /**
     * @var boolean
     * Alters graph built to be more user friendly
     * Replaces RDF notations/conventions with more human readable outputs
     * Does the following:
     *  -Replaces objects with the first relevant label
     *  -Strips IRI's from display
     */
    protected $easyRead;

    protected $nodes = [];
    protected $edges = [];
    protected $cat = [];

    /**
     * GraphBuilder constructor.
     * @param bool $easyRead
     */
    public function __construct(\EasyRdf_Graph $easyRdfGraph, bool $easyRead){
        $this->easyRdfGraph= $easyRdfGraph;
        $this->easyRead = $easyRead;
    }

    /**
     * A utility function, takes a easy_rdf resource (node) and returns the node in
     * an associate array
     * @param $resource EasyRdf_Literal|EasyRdf_Resource the RDF node ro turn into
     * a class
     * @return bool|array the node in an associative array or false if no add should happen
     */
    public function buildNode($resource){

        //don't add Owl:class
        if($this->getID($resource) == "http://www.w3.org/2002/07/owl#Class")
            return array();

        //If the node is a label
        if(is_a($resource, 'EasyRdf_Literal') && !$this->easyRead){
            return array('id'=>$resource->getValue(),
                'label' => $resource->getvalue(),
                /*'color' => '#edbe13',*/
                'shape' => 'rect',
                'category' => $this->getType($resource)
            );
        } //if the node is a resource
        else if(is_a($resource, 'EasyRdf_Resource')) {
            if($this->easyRead){
                if($resource->type() == null)
                    return false;

                //label replace (favour "CanonicalName" as label)
                $label = $resource->getLiteral("ns2:CanonicalName");
                Helper::log("in build node, getting CanonicalName: " . $label);
                if(!$label) {
                    $label = $resource->getLiteral("rdfs:label");
                    Helper::log("CanonicalName null,  getting label: " . $label);
                    if (!$label) {
                        $label = $resource->localName();
                        Helper::log("Label was null, adding instead: " . $label);
                    } else
                        $label = $label->getvalue();
                }
                else
                    $label = $label->getvalue();

                //get content value for tooltip
                $tooltip = $resource->getLiteral("ns2:Content");
                if(!$tooltip) {
                    $tooltip = $resource->localName();
                } else
                    $tooltip = $tooltip->getvalue();

                //change shape based on type
                $shape = "";
                switch ($this->getType($resource)){
                    case 'Program':
                        $shape = 'triangle';
                        break;
                    case 'Outcome':
                        $shape = 'rect';
                        break;
                    default:
                        $shape = 'circle';
                }

                //dosize
                $linkCount = 0;
                if($this->getType($resource) == "CommonwealthBody") {
                    foreach ($resource->properties() as $edgeTypeName) {
                        if ($edgeTypeName == "rdf:type")
                            continue;

                        $linkCount += sizeof($resource->allResources($edgeTypeName));
                        Helper::log("counting edgenum for " . $resource->localName() .
                            "edge " . $edgeTypeName . " has " . sizeof($resource->allResources($edgeTypeName)) .
                            "edges for running total of: " . $linkCount);
                    }
                }

                return array('id' => $this->getID($resource),
                    'label' => $label,
                    /*'color' => '#1969c7',*/
                    'value' => $tooltip,
                    'shape' => $shape,
                    'symbolSize' => strval(10 + ($linkCount * 2)),
                    'category' => $this->getType($resource)
                );
            } else {
                return array('id' => $this->getID($resource),
                    'label' => $resource->localName(),
                    /*'color' => '#1969c7',*/
                    'shape' => 'circle',
                    'category' => $this->getType($resource)
                );
            }
        }
        return false;
    }

    /**
     * Builds an edge from two resource nodes and returns an associative array
     * @param $a EasyRdf_Literal|EasyRdf_Resource the Easy_RDF source node
     * @param $edgeName String the name of the edge
     * @param $b EasyRdf_Literal|EasyRdf_Resource the Easy_RDF target node
     * @return array the edge as an associative array
     */
    public function buildEdge($a, $edgeName, $b){

        if($this->easyRead)
            $edgeName = substr($edgeName, strpos($edgeName, ':') + 1);

        $emphasis = "false";
        //both are resources and not literals
        if(is_a($a, 'EasyRdf_Resource') && is_a($b, 'EasyRdf_Resource')){

            Helper::log("Edge creation: both nodes, checking emphasis for type " .
                $this->getType($a) . " and type " . $this->getType($b));

            if ($this->getType($a) == "CommonwealthBody" &&
                ($this->getType($b) == "Program" || $this->getType($b) == "Outcome"))
                $emphasis = "true";
            else if (($this->getType($a) == "Program" || $this->getType($a) == "Outcome") &&
                $this->getType($b) == "CommonwealthBody")
                $emphasis = "true";

            Helper::log("Emphasis = " . $emphasis);
        }

        $edge = array(
            'sourceID' => $this->getID($a),
            'label' => $edgeName,
            'emphasis' => $emphasis,
            'targetID' => $this->getID($b));

        $this->edges[] = $edge;
        return $edge;
    }

    /**
     * As EasyRdf_Literal and EasyRdf_Resource are commonly use in the same
     * interface but uuids are accessed diffrently, this function resolves that issue
     * @param $resource EasyRdf_Literal|EasyRdf_Resource the node to get the id for
     * @param bool $localName if the local name (i.e post prefix) should be used instead of
     *      full name
     * @return string|null the unique identifier for the resource
     */
    public function getID($resource,  bool $localName = true){
        if(is_a($resource, 'EasyRdf_Literal'))
            return $resource->getValue();
        else if(is_a($resource, 'EasyRdf_Resource'))
            if($localName == true)
                return $resource->localName();
            else
                return $resource->getUri();
    }

    /**
     * Gets the foremost property type of a given resource
     * @param $resource EasyRdf_Literal|EasyRdf_Resource resource to get the type of
     * @return string the type of the node as a string
     */
    public function getType($resource){

        $type = '';
        if (is_a($resource, 'EasyRdf_Literal')) {
            $type = 'Label';
        } else if ($resource->types() != null) {
            foreach ($resource->types() as $type)
                if($type != 'owl:NamedIndividual') //ensure we use a more helpful label
                    return substr($type, strpos($type, ':') + 1); //remove prefix
        } else {
            $type = 'misc';
        }
        return $type;
    }

    public function getJsonGraph(){
        return json_encode(array(
            'category' => array_values($this->cat),
            'nodes' => array_values($this->nodes),
            'edges' => array_values($this->edges)
            )
        );
    }
}