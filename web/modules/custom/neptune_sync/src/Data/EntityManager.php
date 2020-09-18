<?php


namespace Drupal\neptune_sync\Data;


use Drupal\Core\Entity\EntityInterface;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

class EntityManager
{
    /**
     * @var array['Name' => id] special case of ['type' => 'ENTTYPE']
     */
    protected $entHash;

    public function __construct(){

        //TODO can type be removed
        $this->entHash = array(
            "cooperative_relationships" => array('type' => SophiaGlobal::NODE), //may not be inited proprly
            "portfolios" => array('type' => SophiaGlobal::NODE),
            "bodies" => array('type' => SophiaGlobal::NODE),
            "outcome" => array('type' => SophiaGlobal::TAXONOMY),
            "program" => array('type' => SophiaGlobal::TAXONOMY),
            );
    }

    /**
     * @param $entTitle
     * @param $entType
     * @param bool $canCreate
     * @return mixed|string|null
     */
    public function getEntityId($entTitle, $entType, $temp, bool $canCreate = false){
        if($this->entHash[$entType]['type'] == SophiaGlobal::NODE) {                    //if Node
            $query = \Drupal::entityQuery(SophiaGlobal::NODE)
                ->condition('title', $entTitle)
                ->condition('type', '$entType')
                ->execute();
        } else if ($this->entHash[$entType]['type'] == SophiaGlobal::TAXONOMY){    //if Tax
            $query = \Drupal::entityQuery(SophiaGlobal::TAXONOMY)
                ->condition('name', $entTitle)
                ->condition('vid', '$entType')
                ->execute();
        } else {
            Helper::log("Err100: Something went seriously wrong", $event = true);
            return null;
        }

        if(count($query) == 0 && $canCreate) //ent doesnt exist
            $entId = $this->createEntity($entTitle, $entType);
        else
            $entId = reset($query);

        return $entId;
    }

    /**
     * @param DrupalEntityExport $classModel
     * @param bool $canCreate
     * @return String|null entity id
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function getEntityIdFromHash(DrupalEntityExport $classModel,
                                        bool $canCreate = false){

        /** @var  $entHash array[] local scope of form array('label' => 'id')*/
        $entHash = $this->getEntTypeIdHash($classModel);
        Helper::log("Reporting to kint");
        if(array_key_exists($classModel->getLabelKey(), $entHash))
            return $entHash[$classModel->getLabelKey()];
        else if ($canCreate){ //create then add to hash and relationship
            return $this->createEntity($classModel);
        }

        Helper::log("Err501: Something went seriously wrong \n" .
            "\t\t\tcase: getEntityIdFromHash() Entity doesn't exist and not allowed to create one." .
            " Null seeded.\n \t\t\tEntity details:\n\t\t\t " . $classModel->getLabelKey());
        return null;
    }

    /**
     * @variable NodeInterface $nodes
     * @param DrupalEntityExport $classModel
     * @return String[] ['Ent title' => 'id'] of entName
     */
    protected function getEntTypeIdHash(DrupalEntityExport $classModel){

        Helper::log("getting hash map of type " . $classModel->getSubType() .
            " size=" . count($this->entHash[$classModel->getSubType()]));

        //if hash not built, build hash
        if(count($this->entHash[$classModel->getSubType()]) < 2 ) { // < 2 as hardcoded 'type' index
            Helper::log("creating hash for " . $classModel->getSubType());
            if($classModel->getEntityType() == SophiaGlobal::NODE) {
                $nodes = $this->getAllNodeType($classModel->getSubType());
                foreach ($nodes as $node)
                    $this->entHash[$classModel->getSubType()] +=
                        array($node->getTitle() => $node->id());
            } elseif($classModel->getEntityType() == SophiaGlobal::TAXONOMY) {
                $terms = $this->getAllTaxonomyType($classModel->getSubType());
                foreach ($terms as $term)
                    $this->entHash[$classModel->getSubType()] +=
                        array($term->getName() => $term->id());
            }
        }
        return $this->entHash[$classModel->getSubType()];
    }

    /**
     * @param DrupalEntityExport $classModel
     * @return int|string|null
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function createEntity(DrupalEntityExport $classModel){

        Helper::log("creating entity! label: ". $classModel->getLabelKey());

        if($classModel->getEntityType() == SophiaGlobal::NODE)              //create
            $my_ent = Node::create(
                ['type' => $classModel->getSubType()]);
        elseif($classModel->getEntityType() == SophiaGlobal::TAXONOMY)
            $my_ent = Term::create(
                ['vid' => $classModel->getSubType()]);

        foreach($classModel->getEntityArray() as $fieldKey => $fieldVal)    //save fields
            $my_ent->set($fieldKey, $fieldVal);                             //polymorphic
        $my_ent->enforceIsNew();                                            //marks as new
        $my_ent->save();                                                    //save

        $this->entHash[$classModel->getSubType()] +=
            array($classModel->getLabelKey() => $my_ent->id());

        return $my_ent->id();
    }

    /**
     * @param $nodeType
     * @return NodeInterface[]
     */
    public function getAllNodeType($nodeType){
        $nids = \Drupal::entityQuery('node')
            ->condition('type', $nodeType)
            ->execute();
        return Node::loadMultiple($nids);
    }

    /**
     * @param $vocabName
     * @return \Drupal\taxonomy\TermInterface[]
     */
    private function getAllTaxonomyType($vocabName){
        $tids = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', $vocabName)
            ->execute();
        return Term::loadMultiple($tids);
    }
}