<?php

namespace Drupal\neptune_sync\Controller;

use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends ControllerBase
{
    public function buildLocalGraph(NodeInterface $node)
    {
        $build = [
            '#markup' => $this->t('hello ' . $node->getTitle()),
        ];
        return $build;
    }
}