<?php

namespace Drupal\neptune_sync\Controller;

use Drupal\neptune_sync\Graph\GraphGenerator;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends ControllerBase
{
    public function buildLocalGraph(NodeInterface $node)
    {
        if (true){
            $graphGen = new GraphGenerator();
            $path = $graphGen->generateGraph(null);

            $build = [
                '#markup' => '<img src="/drupal8/web/' . $path . '">',
            ];
        }
        else {
            $build = [
                '#markup' => $this->t('Graph too large to generate'),
            ];
        }

        return $build;
    }
}