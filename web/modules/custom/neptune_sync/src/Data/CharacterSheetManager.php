<?php
namespace Drupal\neptune_sync\Data;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\neptune_sync\Data\Model\CharacterSheet;
use Drupal\neptune_sync\Data\Model\CooperativeRelationship;
use Drupal\neptune_sync\Data\Model\TaxonomyTerm;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\node\Entity\Node;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;


class CharacterSheetManager
{
    protected $body;
    protected $query_mgr;
    protected $ent_mgr;
    protected $countSkip;
    protected $countupdated;

    public function __construct(){
        $this->query_mgr = new QueryManager();
        $this->ent_mgr = new EntityManager();
        $this->countSkip = 0;
        $this->countupdated = 0;
    }

    /**
     * @param NodeInterface $node
     * @param bool $bulkOperation
     * @TODO remove N/A once all neptune queries are complete and tested
     */
    public function updateCharacterSheet(NodeInterface $node, Bool $bulkOperation = false){

        $this->body = new CharacterSheet($node->getTitle());

        $this->processPortfolio($node, $bulkOperation);
        $this->processBodyType($node);
        $this->processFinClass($node);
        $this->processLegislation($node, $bulkOperation);
        $this->processEcoSector($node);
        $this->processLink($node);
        $this->processEmploymentType($node);
        $this->processCooperativeRelationships($node, $bulkOperation);

        try {
            $this->updateNode($node);
        } catch (EntityStorageException|MissingDataException $e) {
            Helper::log("Err505: Update did not occur", true);
        }
    }

    public function updateAllCharacterSheets(){

        Helper::log("starting new bulk run", true);

        $bodies = $this->ent_mgr->getAllNodeType("bodies");
        foreach($bodies as $bodyItr){
            $this->updateCharacterSheet($bodyItr, true);
        }
    }

    /**
     * @param NodeInterface $node
     * @param bool $bulkOperation
     * Logic:
     *      -Get and execute query to get portfolio from body
     *      -Decode json result from query to php object
     *      -Get nid of portfolio from the portfolios label
     *      -add nid as entity reference to body
     */
    private function processPortfolio(NodeInterface $node, Bool $bulkOperation = false){

        $portNid = $this->ent_mgr->getEntityIdFromQuery(
            QueryBuilder::getBodyPortfolio($node),
            'portlabel',
            'portfolios',
            $bulkOperation
        );

        if(count($portNid) > 1)
            Helper::log("fill this later", true); //TODO
        else if(count($portNid) == 1)
            $this->body->setPortfolio(reset($portNid));
    }

    /**
     * @param NodeInterface $node
     * @param bool $bulkOperation
     */
    private function processLegislation(NodeInterface $node,
                                        Bool $bulkOperation = false){

        foreach ($this->ent_mgr->getEntityIdFromQuery(
                                    QueryBuilder::getBodyLegislation($node),
                                    'legislationLabel',
                                    'legislation',
                                    $bulkOperation
        ) as $legislationNid)
            $this->body->addLegislations($legislationNid);
    }

    /** Body Type
     * @param NodeInterface $node
     *       Non-corporate Commonwealth entity 87 | Corporate Commonwealth entity 88 | Commonwealth company 90 |
     * @todo add comcompany, maybe as default?| Force to use graph 0 */
    private function processBodyType(NodeInterface $node){

        $vals =['NonCorporateCommonwealthEntity' => 87, 'CorporateCommonwealthEntity' => 88,
            "NEPTUNEFIELD" /* can this be defaulted? */=> 90, 'NA' => 153];
        $res = $this->check_property($vals, $node);
        if(!$res)
            $res = $vals['NA'];
        $this->body->setTypeOfBody($res);
    }

    /**
     * @param NodeInterface $node
     * Economic Sector
     *      General Government Sector 91 | Public Financial Corporation 94 | Public Nonfinancial Corporation 92 | N/A 149
     * @TODO add other terms| Force to use graph 0 */
    private function processEcoSector(NodeInterface $node){

        $vals =['GeneralGovernmentSectorEntity'=> 91, 'NEPTUNEFIELD' => 94, 'NEPTUNEFIELD' => 92, 'NA' => 149] ;
        $res = $this->check_property($vals, $node);
        if($res == null)
            $res = $vals['NA'];
        $this->body->setEcoSector($res);
    }

    /**
     * @param NodeInterface $node
     * Financial classification
     *      Material 95, Government Business Enterprise 96, Non-Material 109
     *  @TODO Force to use graph 0
     */
    private function processFinClass(NodeInterface $node){

        $vals =['MaterialEntity' => 95,  'CommonwealthCompany' => 96, 'NonMaterialEntity' => 109];
        $res = $this->check_property($vals, $node);
        if($res == null)
            $res = $vals['NonMaterialEntity'];
        $this->body->addFinClass($res);
    }

    /**
     * @param NodeInterface $node
     * Employment type
     *      Public Service Act 1999 123 | Non-Public Service Act 1999 124 | Both 125 | Parliamentary Service Act 1999 126 | N/A 151
     * @TODO everything | Force to use graph 0*/
    private function processEmploymentType(NodeInterface $node){

        $vals =['NEPTUNEFIELD' => 123, 'NEPTUNEFIELD' => 124, 'NEPTUNEFIELD' => 125, 'NEPTUNEFIELD' => 126, 'NA' => 151];
        $res = null; //this is killing it
        if($res == null)
            $res = $vals['NA'];
        $this->body->setEmploymentType($res);
    }

    private function processLink(NodeInterface $node){

        $query = QueryBuilder::getResourceLink($node);
        $json = $this->query_mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        foreach ($jsonObj->{'results'}->{'bindings'} as $obj){
            $this->body->setLink($obj->{'link'}->{'value'});
        }
    }

    /**
     * @param NodeInterface $node
     * @param bool $bulkOperation
     */
    private function processCooperativeRelationships(
        NodeInterface $node,  Bool $bulkOperation = false){

        /**TODO: CHANGE THIS /W SINGLE EXE **/
        $bulkOperation = true;

        //get all cooperative relationships from Sparql for the node body
        $query = QueryBuilder::getCooperativeRelationships($node);
        $json = $this->query_mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        //no results
        if (count($jsonObj->{'results'}->{'bindings'}) == 0) {
            return;
        }

        //map results
        foreach ($jsonObj->{'results'}->{'bindings'} as $obj) {

            $relationship = new CooperativeRelationship();
            $relationship->setOwner($node->id());
            $relationship->setProgram( $obj->{'progLabel'}->{'value'});
            $relationship->setProgramDesc($obj->{'progDesc'}->{'value'});
            $relationship->setOutcome( $obj->{'outcomeLabel'}->{'value'});
            $relationship->setOutcomeDesc($obj->{'outcomeDesc'}->{'value'});

            //if bulk as we create a hash
            $receiver = $this->ent_mgr->getEntityId(
                new namespace\Model\ Node(
                    $obj->{'ent2Label'}->{'value'}, 'bodies'),
                false, $bulkOperation);

            if($receiver) {
                Helper::log("Coop rel found");
                $relationship->setReceiver($receiver);
                //add relationship to body, if relationship doesnt exist, add it
                $this->body->addCooperativeRelationships(
                    $this->ent_mgr->getEntityId($relationship, True, $bulkOperation));
            }
        }
    }

    /**
     * @param $res string result of an ASK query in json
     * @return mixed returns the a php boolean on the results of a ASK query
     */
    private function evaluate($res){
        $obj = json_decode($res);
        return $obj->{'boolean'};
    }

    /**
     * @param $vals array list of neptune label strings to attempt to match
     * @param $node
     * @return false|mixed
     *
     * Checks if a (Var) label can be found from a passed in nodes label
     */
    private function check_property($vals, $node){
        foreach (array_keys($vals) as $val){

            $query = QueryBuilder::checkAskBody($node, $val);
            $json = $this->query_mgr->runCustomQuery($query);
            if ($this->evaluate($json))
                return $vals[$val];
        }
        return false;
    }

    /**
     * @param NodeInterface $node
     * @todo this needs rewriting to use the new entity api
     */
    private function updateNode(NodeInterface $node){

        $toUpdate = true;

        /*if($this->shouldUpdate($editNode, "field_portfolio",
            $this->body->getPortfolio())) {

            $toUpdate = true;
            $editNode->field_portfolio =
                array(['target_id' => $this->body->getPortfolio()]);
        }
        if($this->shouldUpdate($editNode, "field_type_of_body",
            $this->body->getTypeOfBody())) {

            $toUpdate = true;
            $editNode->field_type_of_body =
                array(['target_id' => $this->body->getTypeOfBody()]);
        }
        if($this->shouldUpdate($editNode, "field_economic_sector",
            $this->body->getEcoSector())) {

            $toUpdate = true;
            $editNode->field_economic_sector =
                array(['target_id' => $this->body->getEcoSector()]);
        }
        if($this->shouldUpdate($editNode, "field_financial_classification",
            $this->body->getFinClass())) { //@todo multiplicity

            $toUpdate = true;
            $editNode->field_financial_classification =
                array(['target_id' => $this->body->getFinClass()]);
        }
        if($this->shouldUpdate($editNode, "field_employment_arrangements",
            $this->body->getEmploymentType())) {

            $toUpdate = true;
            $editNode->field_employment_arrangements =
                array(['target_id' => $this->body->getEmploymentType()]);
        }
        if($this->shouldUpdate($editNode, "field_enabling_legislation_and_o",
            $this->body->getLegislations())){

            $toUpdate = true;
            $editNode->field_enabling_legislation_and_o = array(); //clear current vals
            foreach($this->body->getLegislations() as $nid)
                $editNode->field_enabling_legislation_and_o[] = ['target_id' => $nid];
        }

        if($this->shouldUpdate($editNode, "field_cooperative_relationships",
            $this->body->getCooperativeRelationships())){

            $toUpdate = true;
            $editNode->field_cooperative_relationships = array(); //clear current vals
            foreach($this->body->getCooperativeRelationships() as $nid)
                $editNode->field_cooperative_relationships[] = ['target_id' => $nid];
        }*/


        /** todo
         * field_accountable_authority_or_g
         * field_ink
         * field_reporting_arrangements
         */

        //$editNode->field_employed_under_the_ps_act = array(['target_id' => $this->body->getPsAct()]);
        if($toUpdate) {
            $this->ent_mgr->updateEntity($this->body, $node->id());
            $this->countupdated++;
            Helper::log("updating body " . $node->id() . " | Updated: " .
                $this->countupdated . "\tSkipped: " . $this->countSkip, true);
        } else {
            $this->countSkip++;
            Helper::log("skipping " . $node->id(), true);
        }
    }

    //how do we handle a removal

    /**
     * @param NodeInterface $editNode
     * @param String $nodeField values currently on drupal
     * @param String|String[] $compVal values in model sourced from neptune
     * @return bool if update should take place
     * @throws MissingDataException
     * TODO rename $compVal to neptune val
     */
    private function shouldUpdate (NodeInterface $editNode, String $nodeField, $compVal){

        Helper::log("shouldUpdate () attempting " . $nodeField);

        //multi field | if either field is a multi val
        Helper::log("comp val count:" . count($compVal), false, $compVal);
        Helper::log("node vals count:" . count($editNode->get($nodeField)->getValue()),
            false, array_merge(...$editNode->get($nodeField)->getValue()));

        $nodeFieldArr = array();
        foreach ($editNode->get($nodeField)->getValue() as $val)
           $nodeFieldArr[] = $val['target_id'];

        Helper::log("modded node val", false, $nodeFieldArr);

        //multi
        if (is_array($compVal) || count($editNode->get($nodeField)->getValue()) > 1) {
            Helper::log("shouldUpdate () multi match " . $nodeField);
            if ($nodeFieldArr != $compVal ||
                count($compVal) != count($editNode->get($nodeField)->getValue())){
                Helper::log("UPDATE FIELD!0");
                return true;
            }
        } else { //single field
            Helper::log("shouldUpdate () single match " . $nodeField);

            //if node has no value and neptune does
            if ($editNode->get($nodeField)->first() == null)
                if ($compVal) {
                    Helper::log("UPDATE FIELD!1");
                    return true;
                } else
                    return false;
            //if node has value and neptune doesnt
            else if(!$compVal && $editNode->get($nodeField)->first() != null) {
                Helper::log("UPDATE FIELD!3");
                return true;
            }

            $array = $editNode->get($nodeField)->first()->getValue();

            //if neptune has a value and neptune does not equal node
            if ($compVal && reset($array) != $compVal) {
                Helper::log("UPDATE FIELD!2");
                return true;
            }
        }
        return false;
    }
}