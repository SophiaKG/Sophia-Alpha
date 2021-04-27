<?php
namespace Drupal\neptune_sync\Data;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\neptune_sync\Data\Model\CharacterSheet;
use Drupal\neptune_sync\Data\Model\CooperativeRelationship;
use Drupal\neptune_sync\Querier\Collections\CoopGraphQuerier;
use Drupal\neptune_sync\Querier\Collections\SummaryViewQuerier;
use Drupal\neptune_sync\Querier\QueryBuilder;
use Drupal\neptune_sync\Querier\QueryManager;
use Drupal\neptune_sync\Utility\SophiaGlobal;
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

        Helper::log("updating node: " . $node->id() . " - " . $node->getTitle());
        $this->body = new CharacterSheet($node->getTitle(),
            $node->get("field_neptune_uri")->getString());

        $this->processLeadBody($node);
        $this->processInSummaryView($node);
        $this->processAliases($node);
        $this->processPortfolio($node, $bulkOperation);
        $this->processLegislation($node, $bulkOperation);
        $this->processCooperativeRelationships($node, $bulkOperation);
        $this->processLink($node);
        Helper::setLogMark();
        $this->processBodyType($node);
        $this->processFinClass($node);
        $this->processEcoSector($node);
        $this->processEmploymentType($node);
        $this->processSummaryKeys($node);

        $this->updateNode($node);
    }

    public function updateAllCharacterSheets(){

        Helper::log("starting new bulk run", true);

        $bodies = $this->ent_mgr->getAllNodeType("bodies");
        foreach($bodies as $bodyItr){
            $this->updateCharacterSheet($bodyItr, true);
        }
    }

    private function processAliases(NodeInterface $node){
        Helper::log("Querying aliases: ");
        $query = QueryBuilder::getAliases($node);
        $json = $this->query_mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        foreach ($jsonObj->{'results'}->{'bindings'} as $obj){
            $this->body->addAlias($obj->{'label'}->{'value'});
        }
    }

    private function processInSummaryView(NodeInterface $node){
        Helper::log("Querying InSummaryView: ");
        $query = QueryBuilder::buildAskQuery(
            SummaryViewQuerier::getIsSummaryViewPart($node));
        $this->body->setInSummaryView(
            $this->evaluate($this->query_mgr->runCustomQuery($query)));
    }

    private function processLeadBody(NodeInterface $node){
        Helper::log("Querying LeadBody: ");
        $query = QueryBuilder::checkAskBody($node, 'LeadBody' );
        $this->body->setLeadBody(
            $this->evaluate($this->query_mgr->runCustomQuery($query)));
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

        Helper::log("Querying portfolio: ");
        $portNid = $this->ent_mgr->getEntityIdFromQuery(
            QueryBuilder::getBodyPortfolio($node),
            'port',
            SophiaGlobal::PORTFOLIO,
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

        Helper::log("Querying legislation: ");
        foreach ($this->ent_mgr->getEntityIdFromQuery(
                                    QueryBuilder::getBodyLegislation($node),
                                    'legislation',
                                    SophiaGlobal::LEGISLATION,
                                    $bulkOperation
        ) as $legislationNid)
            $this->body->addLegislations($legislationNid);
    }

    /** Body Type
     * @param NodeInterface $node
     * Adds the nodes body type to flipchartKeys field. This assignment is later synced up
     *  to the bodyType field via body->syncSummaryKeysToFields().*/
    private function processBodyType(NodeInterface $node){

        helper::log("Processing keys for body types: (next three queries) ");
        $vals = SummaryChartKeys::getTaxonomyIDArray('Body type');
        $res = $this->check_term($vals, $node);
        if($res)
            $this->body->addFlipchartKey($res);
    }

    /** Eco sector
     * @param NodeInterface $node
     * Adds the nodes eco type to flipchartKeys field. This assignment is later synced up
     *  to the EcoSector field via body->syncSummaryKeysToFields().
     * More then one value can be assigned. */
    private function processEcoSector(NodeInterface $node){

        Helper::log("Processing keys for eco sector: (next three)");
        $vals = SummaryChartKeys::getTaxonomyIDArray('Eco Sector');
        foreach ($vals as $key => $val) { //as its a multi field
            helper::log("Processing key = " .
                SummaryChartKeys::getKeyNameFromtaxId($val));
            $res = $this->check_term([$key => $val], $node);
            if ($res)
                $this->body->addFlipchartKey($res);
        }
    }

    /** Financial classification
     * @param NodeInterface $node
     * Adds the nodes fin class to flipchartKeys field. This assignment is later synced up
     *  to the finType field via body->syncSummaryKeysToFields().
     * More then one value can be assigned. */
    private function processFinClass(NodeInterface $node){

        Helper::log("Processing keys for financial class: (next two)");
        $vals = SummaryChartKeys::getTaxonomyIDArray('Fin class');
        foreach ($vals as $key => $val) { //as its a multi field
            helper::log("Processing key = " .
                SummaryChartKeys::getKeyNameFromtaxId($val));
            $res = $this->check_term([$key => $val], $node);
            if ($res)
                $this->body->addFlipchartKey($res);
        }
    }

    /**
     * @param NodeInterface $node
     * Employment type
     * Adds the nodes employment type to flipchartKeys field. This assignment is later synced up
     *  to the employmentType field via body->syncSummaryKeysToFields().*/
    private function processEmploymentType(NodeInterface $node){

        Helper::log("Processing keys for employment type: (next three)");
        $res = "";
        foreach (SummaryChartKeys::getKeys()['Employment type'] as $arrKey => $key){
            helper::log("Processing key = " . $arrKey);
            switch ($arrKey) {
                case 'PS Act': //its the default value if no assignment could be made
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getPsActPart($node));
                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;

                case '^': //!ps act
                    break;

                case '#': // PS act && enabling legislation
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getStaffingWithLegislationPart(
                            $node));

                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;

                case '▲': //parliamentary act
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getParliamentaryActPart($node));
                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;
            }
        }

        Helper::log("res = " . $res);
        /** XXX employment type needs to be made a multi field before the below can be enabled */
        //if ($res != 'PS Act'|| $res != '#') //Not ps act or # than must be ^
        if ($res == '')
            $res = SummaryChartKeys::getKeys()['Employment type']['^']['TaxonomyId'];

        $this->body->addFlipchartKey($res);
    }

    private function processSummaryKeys(NodeInterface $node){

        Helper::log("Processing keys for misc flipchart keys: (next 8)");
        foreach (SummaryChartKeys::getKeys()['Default'] as $arrKey => $key) {
            Helper::log("Processing Key: " . $arrKey);
            $res = false;
            switch ($arrKey) {
                case 'E':
                case 'i':
                    $res = $this->check_term([$key['Neptune_obj'] => $key['TaxonomyId']], $node);
                    break;
                case 'Listed Entities':
                    /** @depecated 22/3/21 AH due to KL feedback. Replaced with body->inSummaryView
                     *  OLD:
                     *   $res = $this->check_term([$key['Neptune_obj'] => $key['TaxonomyId']], $node);
                     */
                    Helper::log("deprecated");
                    break;
                case 'R':
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getRegulatedCorpComEntity(
                            $node));
                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;
                case '*':
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getCorpNonCorpPart($node));
                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;
                case 'HC':
                    /** @deprecated removed via kl feedback v1
                    Helper::log("HC = " . $node->get("field_portfolio")->getString() . "port id = " .  SummaryChartKeys::getAttorneyGeneralsId($this->ent_mgr));
                    if( $node->get("field_portfolio")->getString() ==
                        SummaryChartKeys::getAttorneyGeneralsId($this->ent_mgr))
                        $res = $key['TaxonomyId'];
                     * */
                    break;
                case 'X':
                    $query = QueryBuilder::buildAskQuery(
                        SummaryViewQuerier::getExemptPart($node));
                    if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                        $res = $key['TaxonomyId'];
                    break;
                /**The key is defined as "All non-corporate Commonwealth entities are
                 *  subject to the CPRs." Thus on top of the query below that finds
                 *  explicit CPR's outside of NCCE, we need to assign this record to CPR
                 *  iif it is a NCCE thus establishing precedence of this function.
                 *
                 * @uses $this->processBodyType() must be called first.
                 */
                case '℗':
                    if($this->body->chkFlipchartKey(SummaryChartKeys::getKeys() //if NCCE
                        ['Body type']['Non-corporate Commonwealth entity']['TaxonomyId'])) {
                        $res = $key['TaxonomyId'];
                        Helper::log("no query executed as its a NCCE");
                    } else {
                        $query = QueryBuilder::buildAskQuery(
                            SummaryViewQuerier::getEstablishedByRegulationPart($node));
                        if ($this->evaluate($this->query_mgr->runCustomQuery($query)))
                            $res = $key['TaxonomyId'];
                        break;
                    }
            }
            if ($res)
                $this->body->addFlipchartKey($res);
        }
    }

    private function processLink(NodeInterface $node){

        $query = QueryBuilder::getResourceLink($node);
        $json = $this->query_mgr->runCustomQuery($query);
        $jsonObj = json_decode($json);

        foreach ($jsonObj->{'results'}->{'bindings'} as $obj)
            $this->body->setLink($obj->{'link'}->{'value'});

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
        $query = CoopGraphQuerier::getCooperativeRelationships([$node],
            CoopGraphQuerier::OUTGOING_PROGRAMS);
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
                /* node title is empty as we don't have it from neptune and it's not needed*/
                    "", $obj->{'recBody'}->{'value'},
                    'bodies'),
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
     * @param array $vals of form ['Neptune_obj'] => ['TaxonomyID']
     * @param NodeInterface $node
     * @return false|string The drupal Vid|Nid of the term if it exists in neptune
     */
    private function check_term(array $vals, NodeInterface $node){
        foreach (array_keys($vals) as $val){

            $query = QueryBuilder::buildAskQuery(
                SummaryViewQuerier::getValidatedAuthorityPart($node, $val));
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

        if($toUpdate) {
            Helper::log("syncing keys to fields");
            $this->body->syncSummaryKeysToFields($this->ent_mgr); //adds keys to old form fields
            Helper::log("attempting to update " . $node->id());
            $this->ent_mgr->updateFields($this->body, $node);
            $this->countupdated++;
            Helper::log("updating body " . $node->id() . " Summary View: " .
                $this->body->getInSummaryView() . "\t| Updated: " .
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

    /**
     * @deprecated is this used anymore now that graph0 is dead?
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
}
