<?php


namespace Drupal\neptune_sync\Data;


class SummaryChartKeys{

    private const SummaryChartKey = array(
        'Body type' => array(
            'Non-corporate Commonwealth entity' => array(
                'Neptune_obj' => 'ns2:C2013A00123noncorporateCommonwealthentity',
                'TaxonomyId' => '292',
                'parentId' => '291',
                'FieldTaxID' => '87'),
            'Corporate Commonwealth entity' => array(
                'Neptune_obj' => 'ns2:C2013A00123corporateCommonwealthentity',
                'TaxonomyId' => '293',
                'parentId' => '291',
                'FieldTaxID' => '88'),
            'Commonwealth company' => array(
                'Neptune_obj' => 'ns2:C2013A00123Commonwealthcompany',
                'TaxonomyId' => '294',
                'parentId' => '291',
                'FieldTaxID' => '90')),
        'Fin class' => array(
            'B' => array(
                'Title' => 'Government Business Enterprises',
                'Neptune_obj' => 'ns2:C2013A00123governmentbusinessenterprise',
                'TaxonomyId' => '128',
                'parentId' => '127',
                'FieldTaxID' => '96'),
            'M' => array(
                'Title' => 'Material',
                'Neptune_obj' => 'ns2:EntityListSeriesMaterialEntity',
                'TaxonomyId' => '132',
                'parentId' => '127',
                'FieldTaxID' => '95')),
        'Eco Sector' => array(
            'GGS' => array(
                'Neptune_obj' => 'ns2:EntityListSeriesGeneralGovernmentSector',
                'TaxonomyId' => '141',
                'parentId' => '140',
                'FieldTaxID' => '91'),
            'F' => array(
                'Title' => 'Public Financial Corporation',
                'Neptune_obj' => 'ns2:EntityListSeriesPublicFinancialCorporation',
                'TaxonomyId' => '142',
                'parentId' => '140',
                'FieldTaxID' => '94'),
            'T' => array(
                'Title' => 'Public Non-financial Corporation',
                'Neptune_obj' => 'ns2:EntityListSeriesPublicNon-financialCorporation',
                'TaxonomyId' => '143',
                'parentId' => '140',
                'FieldTaxID' => '92')),
        'Employment type' => array(
            'PS Act' => array(
                'Neptune_obj' => 'NA',
                'TaxonomyId' => '136',
                'parentId' => '135',
                'FieldTaxID' => '123'),
            '^' => array(
                'Neptune_obj' => 'ns2:C2004A00538APSemployment',
                'TaxonomyId' => '137',
                'parentId' => '135',
                'FieldTaxID' => '124'),
            '#' => array(
                'Neptune_obj' => 'ns2:C2004A00538APSemployment',
                'TaxonomyId' => '138',
                'parentId' => '135',
                'FieldTaxID' => '125'),
            '▲' => array(
                'Neptune_obj' => 'ns2:C2004A00536ParliamentaryServiceemployment',
                'TaxonomyId' => '139',
                'parentId' => '135',
                'FieldTaxID' => '126')),
        'Default' => array(
            'E' => array(
                'Title' => 'Executive Agency',
                'Neptune_obj' => 'ns2:C2004A00538ExecutiveAgency',
                'TaxonomyId' => '129',
                'parentId' => '127'),
            'HC' => array(
                'Title' => 'High-Court',
                'Neptune_obj' => 'NA',
                'TaxonomyId' => '130',
                'parentId' => '127'),
            'i' => array(
                'Title' => 'Inter-jurisdictional entities',
                'Neptune_obj' => 'ns2:EntityListSeriesInterJurisdictional',
                'TaxonomyId' => '131',
                'parentId' => '127'),
            'R' => array(
                'Title' => 'Corporate Commonwealth entities',
                'Neptune_obj' => 'ns2:C2013A00123corporateCommonwealthentity',
                'TaxonomyId' => '133',
                'parentId' => '127'),
            '*' => array(
                'Neptune_obj' => 'NA',
                'TaxonomyId' => '134',
                'parentId' => '127'),
            '℗' => array(
                'Title' => 'Commonwealth Procurement Rules',
                'Neptune_obj' => '',
                'TaxonomyId' => '144',
                'parentId' => '148'),
            'X' => array(
                'Title' => 'Government policy orders',
                'Neptune_obj' => '',
                'TaxonomyId' => '145',
                'parentId' => '148'),
            'Listed Entities' => array(
                'Neptune_obj' => 'ns2:C2013A00123listedentity',
                'TaxonomyId' => '147',
                'parentId' => '146')),
    );

    public static function getKeyNameFromtaxId(string $taxId){

        foreach (self::getFlattenSummaryKey() as $key => $val)
            if($val['TaxonomyId'] == $taxId)
                return $key;

        return "Key not found via taxId";
    }

    public static function getKeys(){
        return self::SummaryChartKey;
    }

    public static function getTaxonomyIDArray(string $index){
        $arr = [];
        foreach (self::SummaryChartKey[$index] as $arrKey => $key) {
            $arr[$key['Neptune_obj']] = $key['TaxonomyId'];
        }
        return $arr;
    }

    public static function getFlattenSummaryKey(){
        $retArr =[];
        foreach(self::SummaryChartKey as $types){
            foreach ($types as $arrKey => $keys){
                $retArr[$arrKey] = $keys;
            }
        }
        return $retArr;
    }
}