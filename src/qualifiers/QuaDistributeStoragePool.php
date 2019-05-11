<?php
class QuaDistributeStoragePool extends Qualifier implements InterfaceQualifier
{
    private static $spools = [];
    
    static public function load() {
        $data = HelperVmData::getVmsData();
        $realCvmp = &Cache::$realCvmp;
        $store = [];
        
        //Initial PM Count
        foreach ($realCvmp['rpm'] as $pm => $vms) {
            foreach ($data['pms'][$pm]['datastores'] as $ds) {
                $store['count'][$ds][$pm] = 0;
            }
        }
        
        //Initial VM Count
        foreach ($realCvmp['vmp'] as $vm => $pm) {
            $store['eval'][$vm] = 1;
            foreach ($data['vms'][$vm]['datastores'] as $ds) {
                $store['vms'][$vm] = $ds;
                $store['datastores'][$ds][] = $vm;
                $store['count'][$ds][$pm]++;
            }
        }
        
        foreach ($store['datastores'] as $ds => $vms) {
            $store['avg'][$ds] = count($vms) / count($store['count'][$ds]);
        }
        
        //Saving Evaluations
        foreach ($store['vms'] as $vm => $ds) {
            $pm = $realCvmp['vmp'][$vm];
            $qtd = $store['count'][$ds][$pm];
            $avg = $store['avg'][$ds];
            $eval = QuaDistributeStoragePool::getEval($qtd, $avg);
            $store['eval'][$vm] = $eval;
        }

        $realCvmp[OC_STORE]['QuaDistributeStoragePool'] = $store;
    }
    
    static function evaluate(&$cvmp) {
        $store = & $cvmp[OC_STORE]['QuaDistributeStoragePool'];

        if (!isset($cvmp[OC_LAST_REM_VM])) return $store['eval'];

        $vm = $cvmp[OC_LAST_ADD_VM];
        
        //Get Data
        $newPm = $cvmp[OC_LAST_ADD_PM];
        $oldPm = $cvmp[OC_LAST_REM_PM];
        $ds = $store['vms'][$vm];
        
        //Update PM service Statistics
        $store['count'][$ds][$oldPm]--;
        $store['count'][$ds][$newPm]++;
        
        // update evaluations
        $vms = $store['datastores'][$ds];
        foreach ($vms as $vm) {
            $pm = $cvmp['vmp'][$vm];
            if ($pm != $newPm and $pm != $oldPm) continue;
            $qtd = $store['count'][$ds][$pm];
            $avg = $store['avg'][$ds];
            $store['eval'][$vm] = QuaDistributeStoragePool::getEval($qtd, $avg);
        }
        return $store['eval'];
    }
    
    static function getEval($qtd, $avg) {
        
        if ($qtd == $avg or $qtd <= 1) $eval = 2;
        elseif ($qtd < $avg) $eval = 1;
        else $eval = 0.5;
        
        return $eval;
    }
}
Qualifiers::add('QuaDistributeStoragePool');
