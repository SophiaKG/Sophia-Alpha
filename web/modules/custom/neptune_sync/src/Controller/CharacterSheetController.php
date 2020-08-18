<?php

namespace Drupal\neptune_sync\Controller;


use Drupal\neptune_sync\Data\CharacterSheetManager;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;

class CharacterSheetController extends ControllerBase
{
    public function buildCharacterSheet(NodeInterface $node){
        $c_mgr = new CharacterSheetManager();
        $c_mgr->processPortfolio($node);

        return [
            '#markup' => 'Updating... ' . $node->getTitle() .
                '<div class="back__link">
                    <a href="/drupal8/web/node/' . $node->id() . '">
                        Back
                    </a>
                </div>',
        ];
    }
}