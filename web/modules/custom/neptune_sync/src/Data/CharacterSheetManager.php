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

    public function ctrlTest(NodeInterface $node){


        //body type
        $vals = ['CorporateCommonwealthEntity' => 88, 'NonCorporateCommonwealthEntity' => 87];
        $this->body->setTypeOfBody($this->check_property($vals, $node));

        /** eco sector | General Govt Sector
         * @TODO add other terms */
        $vals =['GeneralGovernmentSectorEntity'=> 91];
        $this->body->setEcoSector($this->check_property($vals, $node));

        //fn class | Material, Government Business Enterprise, Non-Material
        $vals =['MaterialEntity' => 95,  'CommonwealthCompany' => 96, 'NonMaterialEntity' => 109 ];
        $res = $this->check_property($vals, $node);
        if($res == null)
            $res = $vals['NonMaterialEntity'];
        $this->body->setFinClass($res);

        /** PSA 1999
         * @TODO what is M (refer to tax terms?) make this more readable
         */
        $query = QueryBuilder::checkPsAct($node);
        if($this->evaluate($this->query_mgr->runCustomQuery($query)))
            $this->body->setPsAct(100); //psa = yay
        else
            $this->body->setPsAct(101); //psa = no


        $this->updateNode($node);
    }

    private function evaluate($res){
        $obj = json_decode($res);
        return $obj->{'boolean'};
    }

    private function check_property($vals, $node){
        foreach (array_keys($vals) as $val){

            $query = QueryBuilder::checkAskBody($node, $val);
            $json = $this->query_mgr->runCustomQuery($query);
            if ($this->evaluate($json))
                return $vals[$val];
        }
        return false;
    }

    private function processEcoSector(){

    }

    private function processFinClass(){

    }

    private function processPsAct(){

    }

    private function updateNode(NodeInterface $node){
        $editNode = Node::load($node->id());
        Helper::log($node->id());

        if($this->body->getTypeOfBody())
            $editNode->field_type_of_body = array(['target_id' => $this->body->getTypeOfBody()]);
        if($this->body->getFinClass())
            $editNode->field_financial_classification = array(['target_id' => $this->body->getFinClass()]);
        if($this->body->getEcoSector())
            $editNode->field_economic_sector =  array(['target_id' => $this->body->getEcoSector()]);

        $editNode->field_employed_under_the_ps_act = array(['target_id' => $this->body->getPsAct()]);

        $editNode->save();



    }



}