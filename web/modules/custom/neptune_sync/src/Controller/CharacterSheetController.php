<?php

namespace Drupal\neptune_sync\Controller;


use Drupal\neptune_sync\Data\CharacterSheetManager;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;

class CharacterSheetController extends ControllerBase
{
    public function buildCharacterSheet(NodeInterface $node){
        $c_mgr = new CharacterSheetManager();
        $c_mgr->updateCharacterSheet($node);

        return [
            '#markup' => 'Updating... ' . $node->getTitle() .
                '<div class="back__link">
                    <a href="/drupal8/web/node/' . $node->id() . '">
                        Back
                    </a>
                </div>',
        ];
    }

    public function buildAllCharacterSheets(){
        $c_mgr = new CharacterSheetManager();
        $c_mgr->updateAllCharacterSheets();
        Helper::log("Completed cycle of all bodies in drupal", true);

        return [
            '#markup' => 'Updating... hold onto your butts!',
        ];
    }
}