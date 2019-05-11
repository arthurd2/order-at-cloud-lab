<?php
class Approximation
{
    static function getNumberOfPossibilitiesBasedOnRfcAndCvmp($cvmp) {
        $matrix = Approximation::getPossibilityMatrixBasedOnRfcAndCvmp($cvmp);
        return Approximation::getNumberOfPossibilitiesBasedOnMatrix($matrix['vms']);

    }
    static function getPossibilityMatrixBasedOnRfcAndCvmp(&$cvmp) {
        $vms = array_keys($cvmp['vmp']);
        $pms = array_keys($cvmp['pmp']);
        $allowenceMatrix = [];
        foreach ($vms as $vm) {
            foreach ($pms as $pm) {
                if(RulesFreeOfContext::isAllowed($vm,$pm)){
                    $allowenceMatrix['vms'][$vm][$pm] = true; 
                    $allowenceMatrix['pms'][$pm][$vm] = true; 
                }
            }
        }
        return $allowenceMatrix;
    }
    
    static function getNumberOfPossibilitiesBasedOnMatrix($matrix) {
        $retorno = 1;
        foreach ($matrix as $vms) 
            $retorno*= count($vms);
        return $retorno;
    }

    
    static function getAveragePlacementRate(&$cvmp,&$matrix = null){
        if (is_null($matrix)) $matrix = Approximation::getPossibilityMatrixBasedOnRfcAndCvmp($cvmp);
        $retorno = 0;
        foreach ($matrix['vms'] as $pms) 
            $retorno += count($pms);
        return $retorno/count($matrix['vms']);
    }
}
