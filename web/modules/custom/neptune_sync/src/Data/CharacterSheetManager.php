<?php
namespace Drupal\neptune_sync\Data;

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
        $this->processEcoSector($node);
        $this->processEmploymentType($node);
        $this->processFinClass($node);

        $this->updateNode($node);
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


    private function updateNode(NodeInterface $node){
        $editNode = Node::load($node->id());
        Helper::log("updating " . $node->id());

        if($this->body->getPortfolio())
            $editNode->field_portfolio = array(['target_id' => $this->body->getPortfolio()]);
        if($this->body->getTypeOfBody())
            $editNode->field_type_of_body = array(['target_id' => $this->body->getTypeOfBody()]);
        if($this->body->getEcoSector())
            $editNode->field_economic_sector =  array(['target_id' => $this->body->getEcoSector()]);
        if($this->body->getFinClass()) //@todo multiplicity
            $editNode->field_financial_classification = array(['target_id' => $this->body->getFinClass()]);
        if($this->body->getEmploymentType())
            $editNode->field_employment_arrangements = array('target_id' => $this->body->getEmploymentType());
        if($this->body->getLegislations())  //@todo node, multiplicity
            $editNode->field_enabling_legislation_and_o = array(['target_id' => $this->body->getLegislations()]);
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

        $editNode->save();
    }
}