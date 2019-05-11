<?php
class QuaDistributeServices extends Qualifier implements InterfaceQualifier
{

    private static $services = ['paginas', 'nute', 'sites','labeee','labtrans'];

    static public function load() {
        $realCvmp = &Cache::$realCvmp;
        $store = [];
        $store['vms'] = [];

        //Initial PM Count
        foreach (QuaDistributeServices::$services as $service) {
            foreach ($realCvmp['rpm'] as $pm => $vms) {
                $store['count'][$service][$pm] = 0;
            }
        }
        //Initial VM Count
        foreach ($realCvmp['vmp'] as $vm => $pm) {
            $store['eval'][$vm] = 1;
            foreach (QuaDistributeServices::$services as $service) {
                if (stripos($vm, $service) !== false) {
                    $store['vms'][$vm] = $service;
                    $store['services'][$service][] = $vm;
                    $store['count'][$service][$pm]++;
                }
            }
        }

        //Saving Evaluations
        foreach ($store['vms'] as $vm => $service) {
            $pm = $realCvmp['vmp'][$vm];
            $qtd = $store['count'][$service][$pm];
            $eval = QuaDistributeServices::getEval($qtd);
            $store['eval'][$vm] = $eval;
        }
        $realCvmp[OC_STORE]['QuaDistributeServices'] = $store;
    }
    

    static function evaluate(&$cvmp) {
        $store = &$cvmp[OC_STORE]['QuaDistributeServices'];
        if (!isset($cvmp[OC_LAST_REM_VM])) return $store['eval'];
        $vm = $cvmp[OC_LAST_ADD_VM];
        if(!isset($store['vms'][$vm] )) return $store['eval'];
        //Get Data
        $newPm = $cvmp[OC_LAST_ADD_PM];
        $oldPm = $cvmp[OC_LAST_REM_PM];
        $service = $store['vms'][$vm];
        
        //Update PM service Statistics 
        $store['count'][$service][$oldPm]--;
        $store['count'][$service][$newPm]++;
        
        // update evaluations
        $vms = $store['services'][$service];
        foreach ($vms as $vm) {
            $pm = $cvmp['vmp'][$vm];
            if($pm != $newPm and $pm != $oldPm) continue;
            $qtd = $store['count'][$service][$pm];
            $store['eval'][$vm] = QuaDistributeServices::getEval($qtd);
        }
        return $store['eval'];

    }
    
    static function getEval($qtd) {
        if ($qtd <= 1) {
            $eval = 2;
        } 
        elseif ($qtd == 2) {
            $eval = 1;
        } 
        else {
            $eval = 0.5;
        }
        return $eval;
    }
}

Qualifiers::add('QuaConsolidatePm');
