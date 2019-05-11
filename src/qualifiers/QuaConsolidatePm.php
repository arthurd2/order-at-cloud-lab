<?php
class QuaConsolidatePm extends Qualifier implements InterfaceQualifier
{
    private static $vmsData = [];
    
    static public function load() {
        QuaConsolidatePm::$vmsData = HelperVmData::getVmsData();
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
        
        if (!isset($cvmp[OC_STORE]['QuaConsolidatePm'])) $cvmp[OC_STORE]['QuaConsolidatePm'] = [];
        

        $vm = $cvmp[OC_LAST_ADD_VM];
        $curPm = count($cvmp['pmp'][$cvmp[OC_LAST_ADD_PM]]);
        $oriPm = count($cvmp['pmp'][$cvmp[OC_LAST_REM_PM]]);
        
        if ($curPm - 1 == 0) {
            $r =  0.1 ;
        }elseif($oriPm == 0) {
            $r =  1.5;
        }elseif ($curPm - 1 > $oriPm) {
            $r =  1.5;
        }else{
            $r =  1;
        }

        $cvmp[OC_STORE]['QuaConsolidatePm'][$vm] = $r;

        return $cvmp[OC_STORE]['QuaConsolidatePm'];
    }
}
//Qualifiers::add('QuaConsolidatePm');
