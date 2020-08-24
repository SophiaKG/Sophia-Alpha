<?php
namespace Drupal\neptune_sync\Data;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\node\Entity\Node;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;

class CharacterSheetManager
{
    protected $body;
    protected $query_mgr;

    public function __construct(){
        $this->body = new CharacterSheet();
        $this->query_mgr = new QueryManager();
    }

    /**
     * @param NodeInterface $node
     * @TODO remove N/A once all neptune queries are complete and tested
     */
    public function updateCharacterSheet(NodeInterface $node, Bool $bulkOperation = false){


        /** Flipchart keys
         *  E 129| I 131 | R 133 | * 134 | ℗ 144 | X 145
         */

        /** PSA 1999
         * @TODO what is M (refer to tax terms?) make this more readable
         * @TODO remove this
         * this also doesnt work
         */
       /* $query = QueryBuilder::checkPsAct($node);
        if($this->evaluate($this->query_mgr->runCustomQuery($query)))
            $this->body->setPsAct(100); //psa = yay
        else
            $this->body->setPsAct(101); //psa = no
*/
        /** Character sheet booleans
         *  NA 152
         * @TODO these are currently just defaulted values, these need review and hooking up
         */

        $this->processPortfolio($node, $bulkOperation);
        $this->processBodyType($node);
        $this->processFinClass($node);
        $this->processLegislation($node);
        $this->processEcoSector($node);
        $this->processEmploymentType($node);

        //kint($this->body->getLegislations());
        $this->updateNode($node);
        //$this->testfunc($node);
    }

    public function updateAllCharacterSheets(){

        $bodies = $this->getAllNodeType("bodies");
        foreach($bodies as $bodyItr){
            $this->body = new CharacterSheet();
            $this->updateCharacterSheet($bodyItr);
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
    private function processPortfolio(NodeInterface $node, Bool $bulkOperation){

        $query = QueryBuilder::getBodyPortfolio($node);
        $jsonResult = $this->query_mgr->runCustomQuery($query);
        $jsonObject = json_decode($jsonResult);
        if(!is_array($jsonObject->{'results'}->{'bindings'}))//$jsonObject->{'results'}->{'bindings'} < 1) //if no portfolio found
            return;
        $portfolioLabel = $jsonObject->{'results'}->{'bindings'}[0]->{'portlabel'}->{'value'};
        $portNid = null;

        if($portfolioLabel && !$bulkOperation) { //single execution, poll RDS
            $query = \Drupal::entityQuery('node')
                ->condition('title', $portfolioLabel)
                ->condition('type', 'portfolios')
                ->execute();
            $portNid = reset($query);
        } elseif ($portfolioLabel){ //part of a bulk execution, use pre-filled hash table
            $portfolioHash = self::createNodeTypeIdHash('portfolios');
            $portNid = $portfolioHash[$portfolioLabel];
        }

        if($portNid)
            $this->body->setPortfolio($portNid);
    }

    /**
     * @param NodeInterface $node
     */
    private function processLegislation(NodeInterface $node){

        $query = QueryBuilder::getBodyLegislation($node);
        $jsonResult = $this->query_mgr->runCustomQuery($query);
        $jsonObject = json_decode($jsonResult);

        foreach ( $jsonObject->{'results'}->{'bindings'} as $binding){
            $query = \Drupal::entityQuery('node')
                ->condition('title', $binding->{'legislationLabel'}->{'value'})
                ->condition('type', 'legislation')
                ->execute();
            $legislationNid = reset($query);
            $this->body->addLegislations($legislationNid);
        }
    }


    /** Body Type
     * @param NodeInterface $node
     *       Non-corporate Commonwealth entity 87 | Corporate Commonwealth entity 88 | Commonwealth company 90
     * @todo add comcompany, maybe as default?  */
    private function processBodyType(NodeInterface $node){

        $vals =['NonCorporateCommonwealthEntity' => 87, 'CorporateCommonwealthEntity' => 88, "NEPTUNEFIELD" /* can this be defaulted? */=> 90];
        $this->body->setTypeOfBody($this->check_property($vals, $node));
    }

    /**
     * @param NodeInterface $node
     * Economic Sector
     *      General Government Sector 91 | Public Financial Corporation 94 | Public Nonfinancial Corporation 92 | N/A 149
     * @TODO add other terms */
    private function processEcoSector(NodeInterface $node){

        $vals =['GeneralGovernmentSectorEntity'=> 91, 'NEPTUNEFIELD' => 94, 'NEPTUNEFIELD' => 92, 'N/A' => 149] ;
        $res = $this->check_property($vals, $node);
        if($res == null)
            $res = $vals['N/A'];
        $this->body->setEcoSector($res);
    }

    /**
     * @param NodeInterface $node
     * Financial classification
     *      Material 95, Government Business Enterprise 96, Non-Material 109 */
    private function processFinClass(NodeInterface $node){

        $vals =['MaterialEntity' => 95,  'CommonwealthCompany' => 96, 'NonMaterialEntity' => 109];
        $res = $this->check_property($vals, $node);
        if($res == null)
            $res = $vals['NonMaterialEntity'];
        $this->body->setFinClass($res);
    }

    /**
     * @param NodeInterface $node
     * Employment type
     *      Public Service Act 1999 123 | Non-Public Service Act 1999 124 | Both 125 | Parliamentary Service Act 1999 126 | N/A 151
     * @TODO everything */
    private function processEmploymentType(NodeInterface $node){

        $vals =['NEPTUNEFIELD' => 123, 'NEPTUNEFIELD' => 124, 'NEPTUNEFIELD' => 125, 'NEPTUNEFIELD' => 126, 'N/A' => 151];
        $res = null;
        if($res == null)
            $res = $vals['N/A'];
        $this->body->setEmploymentType($res);
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
     * @variable NodeInterface $nodes
     * @param String $nodeType
     * @return String[] ['node title' => 'nid']
     */
    private function createNodeTypeIdHash(String $nodeType){

        static $nodeHash= array();
        if(count($nodeHash) > 1 ) {
            Helper::log("creating hash for " . $nodeType);
            $nodes = $this->getAllNodeType($nodeType);
            foreach ($nodes as $node)
                $nodeHash += array($node->getTitle() => $node->id());
        }

        return $nodeHash;
    }

    /**
     * @param $nodeType
     * @return NodeInterface[]
     */
    private function getAllNodeType($nodeType){
        $nids = \Drupal::entityQuery('node')
            ->condition('type', $nodeType)
            ->execute();
        return Node::loadMultiple($nids);
    }

    private function testfunc($node){
        $editNode = Node::load($node->id());

        Helper::log("class type: " . gettype($editNode->get("field_enabling_legislation_and_o")) . " " .  get_class($editNode->get("field_enabling_legislation_and_o")));
        kint($editNode->get("field_portfolio")->getValue(), "portfolio");
        foreach($editNode->get("field_enabling_legislation_and_o") as $ref) {
            Helper::log("ref type: " .  gettype($ref) . " " .  get_class($ref));
            kint(reset($ref->getValue()), $this->body->getLegislations(), "leg", "count");
        }
        // foreach($editNode->get("field_portfolio") as $ref)
          //  kint(reset($ref->getValue()), $this->body->getPortfolio(), "port", "count");

        /*kint($editNode->get("field_enabling_legislation_and_o")
            ->first()->getValue(), $this->body->getLegislations());

        if(reset($editNode->get("field_portfolio")
            ->first()->getValue()) == $this->body->getPortfolio())
            kint("we be matching");*/
    }

    /**
     * @param NodeInterface $node
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    private function updateNode(NodeInterface $node){

        /** @var NodeInterface $editNode */
        $editNode = Node::load($node->id());
        $toUpdate = false;

        if($this->shouldUpdate($editNode, "field_portfolio",
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
                array('target_id' => $this->body->getEmploymentType());
        }
        if($this->shouldUpdate($editNode, "field_enabling_legislation_and_o",
            $this->body->getLegislations())){

            $toUpdate = true;//clear current vals
            $editNode->field_enabling_legislation_and_o = array();
            foreach($this->body->getLegislations() as $nid)  //@todo multiplicity
                $editNode->field_enabling_legislation_and_o[] = ['target_id' => $nid];
        }

        //$this->testfunc($node);

        //flipkeys

        //default value these nodes until value is complete
        $editNode->field_s35_3_pgpa_act_apply = array(['target_id' => 152]);
        $editNode->field_employed_under_the_ps_act = array(['target_id' => 152]);
        $editNode->field_reporting_variation = array(['target_id' => 152]);
        $editNode->field_cp_tabled = array(['target_id' => 152]);

        /** todo
         * field_accountable_authority_or_g
         * field_ink
         * field_reporting_arrangements
         */

        //$editNode->field_employed_under_the_ps_act = array(['target_id' => $this->body->getPsAct()]);

        if($toUpdate) {
            Helper::log("updating " . $node->id(), true);
            $editNode->setNewRevision();
            $editNode->save();
        } else Helper::log("skipping " . $node->id(), true);
    }

    /** @deprecated  */
    private function xxx (NodeInterface $editNode, String $mode, String $compareVal = ''){
        try{
            switch ($mode) {
                case "portfolio":
                    $array = $editNode->get("field_portfolio")
                        ->first()->getValue(); // may lead to missing data
                    if ($this->body->getPortfolio() &&
                        reset($array) == $this->body->getPortfolio()) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
                case "typeOfBody":
                    $array = $editNode->get("field_type_of_body")
                        ->first()->getValue(); // may lead to missing data
                    if ($this->body->getTypeOfBody() &&
                        reset($array) == $this->body->getTypeOfBody()) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
                case "ecoSector":
                    $array = $editNode->get("field_economic_sector")
                        ->first()->getValue(); // may lead to missing data
                    if ($this->body->getEcoSector() &&
                        reset($array) == $this->body->getEcoSector()) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
                case "finClass": //multiplicity
                    $array = $editNode->get("field_financial_classification")
                        ->first()->getValue(); // may lead to missing data
                    if ($this->body->getFinClass() &&
                        reset($array) == $this->body->getFinClass()) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
                case "employmentType":
                    $array = $editNode->get("field_employment_arrangements")
                        ->first()->getValue(); // may lead to missing data
                    if ($this->body->getEmploymentType() &&
                        reset($array) == $this->body->getEmploymentType()) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
                case "legislation": //multiplicity
                    $array = $editNode->get("field_enabling_legislation_and_o")
                        ->first()->getValue(); // may lead to missing data
                    if ($compareVal &&
                        reset($array) == $compareVal) {
                        $this->toUpdate = true;
                        return true;
                    } else return false;
            }
        }  catch (MissingDataException $e){
            return false;
        }
    }

    //how do we handle a removal

    /**
     * @param NodeInterface $editNode
     * @param String $nodeField
     * @param String|String[] $compVal
     * @return bool
     * @throws MissingDataException
     */
    private function shouldUpdate (NodeInterface $editNode, String $nodeField, $compVal){

        Helper::log("shouldUpdate () attempting" . $nodeField);

        //multi field | if either field is a multi val
        Helper::log("comp val count:" . count($compVal), false, $compVal);
        Helper::log("node vals count:" . count($editNode->get($nodeField)->getValue()),
            false, array_merge(...$editNode->get($nodeField)->getValue()));
        Helper::log($editNode->get($nodeField)->getValue());
        Helper::log(array_merge(...($editNode->get($nodeField)->getValue())));
        kint(
            $editNode->get("field_enabling_legislation_and_o")->getValue(),
            "target id");
        if(is_array($compVal)){
            Helper::log("first match");
        }
        if(count($editNode->get($nodeField)->getValue()) > 1)
        {
            Helper::log("second match size = " . count($editNode->get($nodeField)->getValue()));
        }
        if (is_array($compVal) || count($editNode->get($nodeField)->getValue()) > 1) {
            Helper::log("shouldUpdate () multi match " . $nodeField);
            if (array_merge(...$editNode->get($nodeField)->getValue()) != $compVal ||
                count($compVal) != count($editNode->get($nodeField)->getValue())){
                Helper::log("UPDATE FIELD!0");
                return true;
            }
        } else { //single field
            Helper::log("shouldUpdate () single match " . $nodeField);
            if ($editNode->get($nodeField)->first() == null)
                if ($compVal) {
                    Helper::log("UPDATE FIELD!1");
                    return true;
                } else
                    return false;
            $array = $editNode->get($nodeField)->first()->getValue();
            if ($compVal && reset($array) != $compVal) {
                Helper::log("UPDATE FIELD!2");
                return true;
            }
        }
        return false;
    }
}