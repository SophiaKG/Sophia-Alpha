<?php


namespace Drupal\neptune_sync\Data;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\neptune_sync\Querier\QueryManager;
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
    protected $query_mgr;

    public function __construct(){

        $this->query_mgr = new QueryManager();

        $this->entHash = array(
            "cooperative_relationships" => array(),
            "portfolios" => array(),
            "legislation" => array(),
            "bodies" => array(),
            );
    }

    /**
     * Gets a drupal entity id of a node type.
     * Only works for nodes (not taxonomies) and cannot create
     * if the label of the entity has a corresponding drupal entity id
     * return it. If no Id is found, we can create the entity passed in.
     * Issues can arise if the label used is not unique
     *
     * @TODO make this more dynamic ie use header vars from returned query
     * @param $query
     * @param $nepId String the object uri in neptune
     * @param $nodeType
     * @param bool $bulkOperation
     * @return String[] an array of valid ids
     */
    public function getEntityIdFromQuery($query, $nepId, $nodeType,  Bool $bulkOperation = false){

        $jsonResult = $this->query_mgr->runCustomQuery($query);
        $jsonObject = json_decode($jsonResult);

        $NidArr = array();
        foreach ( $jsonObject->{'results'}->{'bindings'} as $binding) {
            $nid = $this->getEntityId(
                new namespace\Model\ Node("N/A", /*xxx this must be fixed!!!!!!!*/
                    $binding->{$nepId}->{'value'},
                    $nodeType),
                false, $bulkOperation);

            if ($nid == null) {
                Helper::log("Err500:  Something went wrong \n" .
                    "\t\t\tcase: Null Id return when attempting to get an id from a entity label match." .
                    "This might be expected from portfolios or legislations." .
                    "\n\t\t\tDetails: Subtype: " . $nodeType . "\tNeptune Label: " . $nepLabel, true);
            } else
                $NidArr[] = $nid;
        }


        return $NidArr;
    }

    /**
     * if the label of the entity has a corresponding drupal entity id
     *  return it. If no Id is found, we can create the entity passed in.
     *  Issues can arise if the label used is not unique
     *
     * @param DrupalEntityExport $classModel
     * @param bool $canCreate
     * @param bool $bulkOperation
     * @return array|int|mixed|String|null The id of the passed in class (ie. label of ent)
     */
    public function getEntityId(DrupalEntityExport $classModel,
                                bool $canCreate = false,
                                Bool $bulkOperation = false){

        //if single or bulk controller
        if($bulkOperation)
            return $this->getEntityIdBulk($classModel, $canCreate);
        else
            return $this->getEntityIdSingle($classModel, $canCreate);
    }

    /**
     * @param DrupalEntityExport $classModel
     * @param bool $canCreate
     * @return mixed|string|null The id of the passed in class (ie. label of ent)
     *
     * if the label of the entity has a corresponding drupal entity id,
     *      return it. If no Id is found, we can create the entity passed in.
     *      Issues can arise if the label used is not unique
     */
    protected function getEntityIdSingle(DrupalEntityExport $classModel, bool $canCreate = false){
        if($classModel->getEntityType() == SophiaGlobal::NODE) {                    //if Node
            $query = \Drupal::entityQuery(SophiaGlobal::NODE)
                ->condition('field_neptune_uri', $classModel->getIdKey())
                ->condition('type', $classModel->getSubType())
                ->execute();
        } else if ($classModel->getEntityType() == SophiaGlobal::TAXONOMY){    //if Tax
            $query = \Drupal::entityQuery(SophiaGlobal::TAXONOMY)
                ->condition('name', $classModel->getTitle())
                ->condition('vid', $classModel->getSubType())
                ->execute();
        } else {
            Helper::log("Err503-1: Something went seriously wrong\n\t\t\t" .
                'Attempted to create an entity but the entity has no type.' .
                'This really shouldn\'t be able to happen', $event = true);
            return null;
        }

        /** if ent label doesn't match an ent id */
        if(count($query) == 0 && $canCreate) //ent doesnt exist
            $entId = $this->createEntity($classModel);
        else
            $entId = reset($query);

        return $entId;
    }

    /**
     * @param DrupalEntityExport $classModel
     * @param bool $canCreate
     * @return String|null (Drupal) Entity id
     *
     * Logic: if the label of the entity has a corresponding drupal entity id,
     *      return it. If no Id is found, we can create the entity passed in.
     *      Issues can arise if the label used is not unique
     *
     */
    public function getEntityIdBulk(DrupalEntityExport $classModel,
                                        bool $canCreate = false){

        /** @var  $localEntHash array[] local scope of form array('label' => 'id')*/
        $localEntHash = $this->getEntTypeIdHash($classModel);

        if(array_key_exists($classModel->getIdKey(), $localEntHash)) {
            Helper::log("Entity found, referencing " . $classModel->getIdKey());
            return $localEntHash[$classModel->getIdKey()];
        } else if ($canCreate){ //create then add to hash and relationship
            Helper::log("Entity not found, creating");
            return $this->createEntity($classModel);
        }

        Helper::log("Err501: Something went seriously wrong \n" .
            "\t\t\tcase: getEntityIdFromHash() Entity doesn't exist and not allowed to 
            create one. Null seeded\n \t\t\t" . Helper::print_entity($classModel), true);
        return null;
    }

    /**
     * @variable NodeInterface $nodes
     * @param DrupalEntityExport $classModel
     * @return String[] ['idkey' => 'Nid'] of entName
     */
    protected function getEntTypeIdHash(DrupalEntityExport $classModel){

        Helper::log("getting hash map of type " . $classModel->getSubType() .
            " size=" . count($this->entHash[$classModel->getSubType()]));

        //if hash is empty, build hash
        if(count($this->entHash[$classModel->getSubType()]) < 1 ) {
            Helper::log("creating hash for " . $classModel->getSubType());
            if($classModel->getEntityType() == SophiaGlobal::NODE) {
                $nodes = $this->getAllNodeType($classModel->getSubType());
                foreach ($nodes as $node)
                    $this->entHash[$classModel->getSubType()] +=
                        array($node->get("field_neptune_uri")->getString() => $node->id());

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
     * @return int|string|null Entity id of created entity
     *
     */
    public function createEntity(DrupalEntityExport $classModel){

        Helper::log("creating entity! label: ". $classModel->getIdKey());

        if($classModel->getEntityType() == SophiaGlobal::NODE)              //create
            $my_ent = Node::create(
                ['type' => $classModel->getSubType()]);
        elseif($classModel->getEntityType() == SophiaGlobal::TAXONOMY)
            $my_ent = Term::create(
                ['vid' => $classModel->getSubType()]);
        else {
            Helper::log("Err503-3: Something went seriously wrong\n\t\t\t" .
                'Attempted to create an entity but the entity has no type.' .
                'This really shouldn\'t be able to happen', $event = true);
            return null;
        }
        if(!$this->updateFields($classModel, $my_ent, true))
            return false;

        //add to runtime hash
        $this->entHash[$classModel->getSubType()] +=
            array($classModel->getIdKey() => $my_ent->id());

        return $my_ent->id();
    }



    public function updateEntity(DrupalEntityExport $classModel, $entityId){

        if($classModel->getEntityType() == SophiaGlobal::NODE)
            $my_ent = Node::load($entityId);
        elseif($classModel->getEntityType() == SophiaGlobal::TAXONOMY)
            $my_ent = Term::load($entityId);
        else {
            Helper::log("Err503-2: Something went seriously wrong\n\t\t\t" .
                'Attempted to update an entity but the entity has no type.' .
                'This really shouldn\'t be able to happen', $event = true);
            return false;
        }

        return $this->updateFields($classModel, $my_ent);
    }

    /**
     * @param DrupalEntityExport $classModel
     * @param EntityInterface $my_ent
     * @param bool $isNew
     * @return bool
     */
    private function updateFields(DrupalEntityExport $classModel, EntityInterface $my_ent, $isNew = false){

        foreach($classModel->getEntityArray() as $fieldKey => $fieldVal)    //save fields
            $my_ent->set($fieldKey, $fieldVal);                             //polymorphic
        if($isNew)
            $my_ent->enforceIsNew();                                            //marks as new
        if ($my_ent->getEntityType() == SophiaGlobal::NODE)
            $my_ent->setNewRevision();
            $my_ent->setRevisionUserId(SophiaGlobal::MAINTENANCE_BOT);
        try {
            $my_ent->save();
        } catch (EntityStorageException $e) { //save
            Helper::log("Err502-2: Something went seriously wrong\n\t\t\t" .
                'Attempting to save ' . $classModel->getTitle() .
                ' But failed. \n' . $e,  $event = true);
            return false;
        }
        return true;
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

    public function getAllNeptunetypes(){
        $nids = \Drupal::entityQuery(SophiaGlobal::NODE, 'OR')
            ->condition('type', SophiaGlobal::BODIES)
            ->condition('type', SophiaGlobal::PORTFOLIO)
            ->condition('type', SophiaGlobal::LEGISLATION)
            ->condition('type', SophiaGlobal::COOPERATIVE_RELATIONSHIP)
            ->execute();
        return Node::loadMultiple($nids);
    }

    /**
     *
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