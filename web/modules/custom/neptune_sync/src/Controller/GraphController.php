<?php

namespace Drupal\neptune_sync\Controller;

use Drupal\neptune_sync\Graph\GraphGenerator;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends ControllerBase
{
    /**@deprecated by displayIntGraph & echarts
     * @param NodeInterface $node
     * @return string[]
     */
    public function buildLocalGraph(NodeInterface $node){

        $graphGen = new GraphGenerator();
        $path = $graphGen->buildGraphFromNode($node);

        return [
            '#markup' => '<img src="/drupal8/web/' . $path . '">',
        ];
    }

    /**@deprecated by displayIntGraph & echarts
     * @param NodeInterface $node
     * @return array
     */
    public function localGraphQuery(NodeInterface $node){
        return [
            'form' => \Drupal::formBuilder()->getForm('\Drupal\neptune_sync\Form\LocalGraphForm', $node)];

    }

    /**@deprecated by displayIntGraph & echarts
     * @param String $graphid
     * @return string[]
     */
    public function displayGraph(String $graphid){
        return [
            '#markup' => '<img src="/drupal8/web/' . QueryBuilder::GRAPH_WORKING_DIR . $graphid . '">',
        ];
    }

    /**
     * @param NodeInterface $node
     * @return array returns variables to template
     */
    public function displayIntGraph(NodeInterface $node) {
        $graphGen = new GraphGenerator();
        $json = $graphGen->buildGraphFromNode($node);
        Helper::log("Json local Graph for:" . $node->getTitle() . "\n\n\t\t" . $json);

        return [
            '#theme' => 'graph_template',
            '#graph_name' => $this->t($node->getTitle()),
            '#graph_json' => $json,
        ];
    }


    public function displayCoopGraph(NodeInterface $node){
        $graphGen = new GraphGenerator();
        $json = $graphGen->buildCoopGraphFromNode($node);
        Helper::log("Json coop Graph for:" . $node->getTitle() . "\n\n\t\t" . $json);

        return [
            '#theme' => 'graph_coop_relationships_template',
            '#graph_name' => $this->t($node->getTitle()),
            '#graph_json' => $json,
        ];
    }

    public function displayCoopGraphIntersect(Request $request){

        $graphGen = new GraphGenerator();
        $json = $graphGen->buildCoopGraphIntersect( $request->query->get('bodies'));

        return[
            '#theme' => 'graph_coop_relationships_template',
            '#graph_name' => 'Cooperative Graph Intersect',
            '#graph_json' => $json,
        ];
    }


    public function coopGraphQuery(){
        return [
            'form' => \Drupal::formBuilder()->getForm('\Drupal\neptune_sync\Form\CoopGraphForm')];
    }
}
