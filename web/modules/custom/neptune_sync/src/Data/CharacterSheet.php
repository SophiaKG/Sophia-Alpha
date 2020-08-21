<?php
namespace Drupal\neptune_sync\Data;

/**
 * Class CharacterSheet
 */
class CharacterSheet
{

    //Portfolio
    protected $portfolio;
    //Type of Body
    protected $type_of_body;
    //Accountable authority or governing board
    protected $accountable_authority;
    //Economic sector
    protected $eco_sector;
    //Financial classification0
    protected $fin_class;

    /** @deprecated */
    protected $abn;
    //Enabling legislation and other key governance-related details
    protected $legislations = [];
    //Employment type
    protected $employment_type;

    /** Y/N/M
     * @var
     * @label('Employed under the PS Act')
     * @deprecated */
    protected $ps_act;

    public function compare(){

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
    public function getEcoSector()
    {
        return $this->eco_sector;
    }

    /**
     * @return mixed
     */
    public function getFinClass()
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
    public function getEmploymentType()
    {
        return $this->employment_type;
    }

    /**
     * @param mixed $employment_type
     */
    public function setEmploymentType($employment_type): void
    {
        $this->employment_type = $employment_type;
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
    public function setEcoSector($eco_sector): void
    {
        $this->eco_sector = $eco_sector;
    }

    /**
     * @param mixed $fin_class
     */
    public function setFinClass($fin_class): void
    {
        $this->fin_class = $fin_class;
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
    public function addLegislations(String $legislations): void
    {
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


}