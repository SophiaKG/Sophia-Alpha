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
     *  -Strips IRI's from display
     *  -Replaces objects with the first relevant label
     *  -adds tooltips to certain nodes
     *  -changes shape of node based on type
     *  -changes node size based on links
     */
    protected $easyRead;

    protected $nodes = [];
    protected $edges = [];
    protected $cat = [];

    /**
     * GraphBuilder constructor.
     * @param \EasyRdf_Graph $easyRdfGraph the graph to which we build an eChart array from
     * @param bool $easyRead see protected $easyRead
     */
    public function __construct(\EasyRdf_Graph $easyRdfGraph, bool $easyRead){
        $this->easyRdfGraph= $easyRdfGraph;
        $this->easyRead = $easyRead;
    }

    /**
     * A utility function, takes a easy_rdf resource (node) and processes it as an
     * associate array to use for eChart. Saves the node as well as returning it.
     * @uses $easyRead
     * @param $resource EasyRdf_Literal|EasyRdf_Resource the RDF node ro turn into
     * a class
     * @return bool|array the node in an associative array that was saved or false
     *      if the node shouldn't be processed and wasn't added.
     */
    public function buildNode($resource){

        //don't add Owl:class
        if($this->getID($resource) == "http://www.w3.org/2002/07/owl#Class")
            return false;

        $node = false;

        //If the node is a label && not easyRead, literals should not display on easyRead
        if(is_a($resource, 'EasyRdf_Literal') && !$this->easyRead){
            $node = array('id'=>$resource->getValue(),
                'label' => $resource->getvalue(),
                'shape' => 'rect',
                'category' => $this->getType($resource)
            );
        } //if the node is a resource
        else if(is_a($resource, 'EasyRdf_Resource')) {
            if($this->easyRead){  //human readable
                $node = array_merge(
                    array('id' => $this->getID($resource),
                    'category' => $this->getType($resource)
                    ),
                    $this->ProcessEasyReadNode($resource)
                );
            } else { //oncologist readable
                $node = array('id' => $this->getID($resource),
                    'label' => $resource->localName(),
                    'shape' => 'circle',
                    'category' => $this->getType($resource)
                );
            }
        }

        $this->nodes =+ $node;
        return $node;
    }

    /**
     * Alters graph built to be more user friendly
     * Replaces RDF notations/conventions with more human readable outputs
     * Does the following:
     *  -Strips IRI's from display
     *  -Replaces objects with the first relevant label
     *  -adds tooltips to certain nodes
     *  -changes shape of node based on type
     *  -changes node size based on links
     *
     * @param $resource EasyRdf_Resource node to make user friendly
     * @return array|false an associative array to combine with the default values in
     *      $nodes or false if the node is not easyRead viable.
     */
    private function ProcessEasyReadNode($resource){

        /** If the node has no type, don't display it.
         *  XXX what the hell would trigger this?
         */
        if($resource->type() == null)
            return false;

        $nodeRetArr = [];

        /**Label replace
         * Replace object with its label
         * - favour "CanonicalName" as replacement
         * - else, get first 'rdfs:label', at random
         * - else, use local name of object node
         */
        $label = $resource->getLiteral("ns2:CanonicalName");
        Helper::log("In build node, getting CanonicalName: " . $label);
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

        $nodeRetArr += ['label' => $label];

        /** Build tooltip value
         * create a "value" field for node based on:
         * - if the node has a ns2:content edge, use the linked node.
         * - else, use the local name (after ':') of the node
         */
        $tooltip = $resource->getLiteral("ns2:Content");
        if(!$tooltip) {
            $nodeRetArr += ['value' => $resource->localName()];
        } else
            $nodeRetArr += ['value' => $tooltip->getvalue()];

        /** Change shape, based on if the node belong to a certain class.
         *  Currently only works for coop-graph.
         *  Warning: $this->getType returns a single type of the node when many may exist.
         *      Thus it is possible the node may be a "Program" but may not trigger the below
         *      switch.
         */
        switch ($this->getType($resource)){
            case 'Program':
                $nodeRetArr += ['shape' => 'triangle'];
                break;
            case 'Outcome':
                $nodeRetArr += ['shape' => 'rect'];
                break;
            default:
                $nodeRetArr += ['shape' => 'circle'];
        }

        /** Base size of the node on teh graph based on how many outgoing links it has.
         *  Ignore class "rdf:type" links
         */
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
        $nodeRetArr += ['symbolSize' => strval(10 + ($linkCount * 2))];

        return $nodeRetArr;
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
     * interface but uuids are accessed differently, this function resolves that issue
     * @param $resource EasyRdf_Literal|EasyRdf_Resource the node to get the id for
     * @param bool $localName if the local name (i.e post prefix) should be used instead of
     *      full name
     * @return string|null the unique identifier for the resource
     */
    public function getID($resource,  bool $localName = true){
        if(is_a($resource, 'EasyRdf_Literal'))
            return $resource->getValue();
        else
            if(is_a($resource, 'EasyRdf_Resource'))
                if($localName == true)
                    return $resource->localName();
                else
                    return $resource->getUri();
        return false; //is not EasyRdf_Literal|EasyRdf_Resource
    }

    /**
     * Gets the foremost relevant property type of a given resource
     *  -ignores NamedIndividual type
     * @param $resource EasyRdf_Literal|EasyRdf_Resource resource to get the type of
     * @return string the type of the node as a string, stripped from its IRI
     */
    public function getType($resource){

        $type = '';
        if (is_a($resource, 'EasyRdf_Literal')) {
            $type = 'Label';
        } else if ($resource->types() != null) {
            foreach ($resource->types() as $type)
                if($type != 'owl:NamedIndividual') //ensure we use a more helpful label
                    return substr($type, strpos($type, ':') + 1); //remove prefix
        /** what would trigger misc? if the node doesn't have an "rdf:type" or 'a' link.
        * is this even possible in RDF */
        } else
            $type = 'misc';

        return $type;
    }

    /**
     * @return string a Json encoded string with an eChart version of the easyRdf Graph
     */
    public function getJsonGraph(){
        return json_encode(array(
            'category' => array_values($this->cat),
            'nodes' => array_values($this->nodes),
            'edges' => array_values($this->edges)
            )
        );
    }
}