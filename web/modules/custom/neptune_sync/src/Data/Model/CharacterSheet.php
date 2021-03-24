<?php
namespace Drupal\neptune_sync\Data\Model;

use Drupal\neptune_sync\Data\DrupalEntityExport;
use Drupal\neptune_sync\Data\EntityManager;
use Drupal\neptune_sync\Data\SummaryChartKeys;
use Drupal\neptune_sync\Utility\SophiaGlobal;

/**
 * Class CharacterSheet
 */
class CharacterSheet extends Node implements DrupalEntityExport
{
    //Portfolio
    protected $portfolio;
    //Type of Body
    protected $type_of_body;
    //Accountable authority or governing board
    protected $accountable_authority;
    //Economic sector
    protected $eco_sector = [];
    //Financial classification0
    protected $fin_class = [];
    //Employment type
    protected $employment_type = [];
    protected $link;
    protected $legislations = [];
    protected $inSummaryView;


    //flip chart keys
    protected $flipchart_keys = [];

    /** @deprecated */
    protected $abn;
    //Enabling legislation and other key governance-related details


    /** @var String[] node id entity refs */
    protected $cooperativeRelationships = [];

    /** Y/N/M
     * @var
     * @label('Employed under the PS Act')
     * @deprecated */
    protected $ps_act;

    public function compare(){

    }

    public function __construct($title, $neptune_uri){
        parent::__construct($title, $neptune_uri,SophiaGlobal::BODIES);
    }

    //booleans
    /**
     * @return mixed
     */
    public function getPortfolio()
    {
        return $this->portfolio;
    }

    /**
     * @return mixed
     */
    public function getTypeOfBody()
    {
        return $this->type_of_body;
    }

    /**
     * @return mixed
     */
    public function getAccountableAuthority()
    {
        return $this->accountable_authority;
    }

    /**
     * @return mixed
     */
    public function getEcoSectorxx(): array
    {
        return $this->eco_sector;
    }

    /**
     * @return mixed
     */
    public function getFinClass(): array
    {
        return $this->fin_class;
    }

    /**
     * @return mixed
     */
    public function getAbn()
    {
        return $this->abn;
    }

    /**
     * @return array
     */
    public function getLegislations(): array
    {
        return $this->legislations;
    }

    /**
     * @return mixed
     */
    public function getEmploymentTypexx() : array
    {
        return $this->employment_type;
    }

    /**
     *
     */
    public function getPsAct()
    {
        return $this->ps_act;
    }

    /**
     * @return bool
     */
    public function isEnabLegis(): bool
    {
        return $this->enab_legis;
    }

    /**
     * @return bool
     */
    public function isPgpaAct(): bool
    {
        return $this->pgpa_act;
    }

    /**
     * @return bool
     */
    public function isCp(): bool
    {
        return $this->cp;
    }

    /**
     * @return bool
     */
    public function isReportVariation(): bool
    {
        return $this->report_variation;
    }

    /**
     * @var boolean
     * @label('Enabling legislation')
     */
    protected $enab_legis;

    /**
     * @var boolean
     * @label('S35(3) PGPA Act apply')
     */
    protected $pgpa_act;

    /**
     * @var boolean
     * @label('CP tabled')
     */
    protected $cp;

    /**
     * @var boolean
     * @label('Reporting variation')
     */
    protected $report_variation;

    /**
     * @return mixed
     */
    public function getInSummaryView(){
        return $this->inSummaryView;
    }

    /**
     * @param mixed $inSummaryView
     */
    public function setInSummaryView($inSummaryView): void{
        $this->inSummaryView = $inSummaryView;
    }



    /**
     * @param mixed $portfolio
     */
    public function setPortfolio($portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    /**
     * @param mixed $type_of_body
     */
    public function setTypeOfBody($type_of_body): void
    {
        $this->type_of_body = $type_of_body;
    }

    /**
     * @param mixed $accountable_authority
     */
    public function setAccountableAuthority($accountable_authority): void
    {
        $this->accountable_authority = $accountable_authority;
    }

    /**
     * @param mixed $eco_sector
     */
    public function addEcoSector($eco_sector): void
    {
        array_push($this->eco_sector, $eco_sector);
    }

    /**
     * @param mixed $fin_class
     */
    public function addFinClass(String $fin_class): void
    {
        array_push($this->fin_class, $fin_class);
    }

    /**
     * @param mixed $employment_type
     */
    public function addEmploymentType($employment_type): void
    {
        array_push($this->employment_type, $employment_type);
    }

    /**
     * @param mixed $abn
     */
    public function setAbn($abn): void
    {
        $this->abn = $abn;
    }

    /**
     * @param array $legislations
     */
    public function addLegislations(String $legislations): void{
        //check duplicates
        if(!in_array($legislations, $this->legislations))
            array_push($this->legislations, $legislations);
    }

    /**
     * @param $ps_act
     */
    public function setPsAct($ps_act): void
    {
        $this->ps_act = $ps_act;
    }

    /**
     * @param bool $enab_legis
     */
    public function setEnabLegis(bool $enab_legis): void
    {
        $this->enab_legis = $enab_legis;
    }

    /**
     * @param bool $pgpa_act
     */
    public function setPgpaAct(bool $pgpa_act): void
    {
        $this->pgpa_act = $pgpa_act;
    }

    /**
     * @param bool $cp
     */
    public function setCp(bool $cp): void
    {
        $this->cp = $cp;
    }

    /**
     * @param bool $report_variation
     */
    public function setReportVariation(bool $report_variation): void
    {
        $this->report_variation = $report_variation;
    }

    /**
     * @return array
     */
    public function getCooperativeRelationships(): array
    {
        return $this->cooperativeRelationships;
    }

    /**
     * @param array $cooperativeRelationships
     */
    public function addCooperativeRelationships(string $cooperativeRelationships): void
    {
        array_push($this->cooperativeRelationships, $cooperativeRelationships);
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    public function addFlipchartKey(string $key): void {
        array_push($this->flipchart_keys, $key);
    }

    public function chkFlipchartKey(string $key): bool {
        return in_array($key, $this->flipchart_keys);
    }
    /**
     * @param mixed $link
     */
    public function setLink($link): void
    {
        //schema validation
        if(substr($link,0,3) == 'www' ||
            substr($link,0,3) == 'WWW')
            $link = 'http://' . $link;
        $this->link = $link;
    }

    /**
     * As the flipchart key field is the truth and what is used in the character sheet manager,
     *   we need to sync these values over to the individualised sub fields
     * @param EntityManager $ent_mgr A prebuilt ent manager for carrying over the node hashes
     */
    public function syncSummaryKeysToFields(EntityManager $ent_mgr){
        foreach(SummaryChartKeys::getKeys() as $typeKey => $type){
            if($typeKey == 'Default')
                continue;
            else
                foreach ($type as $field)
                    if (in_array($field['TaxonomyId'], $this->flipchart_keys)) {
                        switch ($typeKey){
                            case 'Body type':
                                $this->setTypeOfBody($field['FieldTaxID']);
                                break;
                            case 'Fin class':
                                $this->addFinClass($field['FieldTaxID']);
                                break;
                            case 'Eco Sector':
                                $this->addEcoSector($field['FieldTaxID']);
                                break;
                            case 'Employment type':
                                $this->addEmploymentType($field['FieldTaxID']);
                                break;

                        }
                    }
        }

        //Set the pseudo portfolio "Parliamentary Department" if relevant
        if(in_array(SummaryChartKeys::getKeys()['Employment type']['▲']['FieldTaxID'],
                $this->getEmploymentTypexx())){
            $this->setPortfolio(SummaryChartKeys::getParliamentaryDepartmentId($ent_mgr));
        }
    }

    public function getEntityArray(){

        $retArr =  array(
            'title' =>  $this->getTitle(),
            'field_neptune_uri' => $this->getIdKey(),
            'field_portfolio' => [
                'target_id' => $this->portfolio,
            ],
            'field_type_of_body' => [
                'target_id' => $this->type_of_body,
            ],
            'field_ink' => [  //sadly not a typo
                'uri' => $this->link,
                'title' => 'Homepage',
                'options' => [
                    'attributes' => [
                        'target' => '_blank',
                    ],
                ],
            ],
            'field_in_summary_view' => $this->getInSummaryView(),
            'field_s35_3_pgpa_act_apply' => ['target_id' => 152], //152 is vid for n/a
            //'field_employed_under_the_ps_act' => ['target_id' => 152],
            'field_reporting_variation' => ['target_id' => 152],
            'field_cp_tabled' => ['target_id' => 152],
        );

        /* handle multiplicities */

        //legislation
        $addArr = array();
        foreach($this->legislations as $leg)
            $addArr[] = ['target_id' => $leg];
        $retArr['field_enabling_legislation_and_o'] = $addArr;

        //coop-relations
        $addArr = array();
        foreach ($this->cooperativeRelationships as $rel)
            $addArr[] = ['target_id' => $rel];
        $retArr['field_cooperative_relationships'] = $addArr;

        //fin class
        $addArr = array();
        foreach ($this->getFinClass() as $class)
            $addArr[] = ['target_id' => $class];
        $retArr['field_financial_classification'] = $addArr;

        //eco sector
        $addArr = array();
        foreach ($this->getEcoSectorxx() as $sector)
            $addArr[] = ['target_id' => $sector];
        $retArr['field_economic_sector'] = $addArr;

        //employment type
        $addArr = array();
        foreach ($this->getEmploymentTypexx() as $type)
            $addArr[] = ['target_id' => $type];
        $retArr['field_employment_arrangements'] = $addArr;

        //flipchart keys
        $addArr = array();
        foreach ($this->flipchart_keys as $class)
            $addArr[] = ['target_id' => $class];
        $retArr['field_flipchart_keys'] = $addArr;

        return $retArr;
    }
}