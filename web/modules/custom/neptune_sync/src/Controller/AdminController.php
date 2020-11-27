<?php


namespace Drupal\neptune_sync\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\neptune_sync\Data\NeptuneImporter;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\Entity\Node;

class AdminController extends ControllerBase
{
    public function executeAdminFunc(){
        $this->wipeNeptuneContent();
        return [
            '#markup' => 'Your function was ran',
        ];
    }

    public function syncData(){
        return [
            'form' => \Drupal::formBuilder()->getForm('\Drupal\neptune_sync\Form\DataSyncForm')];
    }

    private function modifyNode(){

        $arr = [
            'uri' => 'http://www.nhfic.gov.au',
            'title' => 'Homepage',
            'options' => [
                'attributes' => [
                    'target' => '_blank',
                ],
            ]
        ];

        $my_ent = Node::load('6803');
        $my_ent->set('field_ink', $arr);
        $my_ent->setNewRevision();
        $my_ent->setRevisionUserId(SophiaGlobal::MAINTENANCE_BOT);
        $my_ent->save();

    }

    private function wipeNeptuneContent(){

        $importer = new NeptuneImporter();
        $importer->importNeptuneData(true);
    }

}