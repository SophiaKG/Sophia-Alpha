<?php

namespace Drupal\neptune_sync\Controller;

use Drupal\neptune_sync\Graph\GraphGenerator;
use Drupal\neptune_sync\querier\QueryBuilder;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends ControllerBase
{
    public function buildLocalGraph(NodeInterface $node){

        $graphGen = new GraphGenerator();
        $path = $graphGen->buildGraphFromNode($node);

        return [
            '#markup' => '<img src="/drupal8/web/' . $path . '">',
        ];
    }

    public function localGraphQuery(NodeInterface $node){
        return [
            'form' => \Drupal::formBuilder()->getForm('\Drupal\neptune_sync\Form\LocalGraphForm', $node)];

    }

    public function displayGraph(String $graphid){
        return [
            '#markup' => '<img src="/drupal8/web/' . QueryBuilder::GRAPH_WORKING_DIR . $graphid . '">',
        ];
    }

    public function testTemplate(string $nodename) {
        return [
            '#theme' => 'graph_template',
            '#test_var' => $this->t($nodename),
        ];

    }
}
