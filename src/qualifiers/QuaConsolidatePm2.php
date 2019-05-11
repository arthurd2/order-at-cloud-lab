<?php
class QuaConsolidatePm2 extends Qualifier implements InterfaceQualifier
{
    private static $vmsData = [];
    private static $pref = [];
    
    static public function load() {
        $cvmp = &Cache::$realCvmp;
        $i = 1;
        foreach ($cvmp['pmp'] as $pm => $pmData) {
            QuaConsolidatePm2::$pref[$pm] = $i++;
        }
    }
    
    /**
     * Evaluation of QuaConsolidatePm
     *     If VM went to an empty PM, return 0.1
     *     If VM empty an PM, return 2
     *     If VM has migrate to a crowded PM = 1.5, otherwise 1
     *
     * @param  array &$cvmp CVMP
     * @return array        Evaluation Array
     */
    static function evaluate(&$cvmp) {
        if (!isset($cvmp[OC_LAST_REM_PM])) return [];
        
        if (!isset($cvmp[OC_STORE]['QuaConsolidatePm2'])) $cvmp[OC_STORE]['QuaConsolidatePm2'] = [];
        
        $weigh = &QuaConsolidatePm2::$pref;
        $vm = $cvmp[OC_LAST_ADD_VM];
        $curPm = $cvmp[OC_LAST_ADD_PM];
        $oriPm = $cvmp[OC_LAST_REM_PM];
        
        if ( $weigh[$curPm] >= $weigh[$oriPm]) 
            $r =  2 ;
        else
            $r =  0.5;

        $cvmp[OC_STORE]['QuaConsolidatePm2'][$vm] = $r;
        return $cvmp[OC_STORE]['QuaConsolidatePm2'];
    }
}
//Qualifiers::add('QuaConsolidatePm');
