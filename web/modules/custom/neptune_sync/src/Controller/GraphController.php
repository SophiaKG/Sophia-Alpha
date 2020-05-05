<?php

namespace Drupal\neptune_sync\Controller;

use Drupal\node;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends ControllerBaselerbase
{
    public function buildLocalGraph(NodeInterface $node, Request $request)
    {
        $build = [
            '#markup' => $this->t('hello world'),
        ];
        return $build;
    }
}