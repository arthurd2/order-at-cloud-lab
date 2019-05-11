<?php
class RfcLiveMigration extends Rule implements RuleFreeOfContext
{
    private static $vmsData = [];
    
    static public function load() {
        RfcLiveMigration::$vmsData = HelperVmData::getVmsData();
    }
    static function isAllowed(&$vm, &$pm) {
        
        $vmd = RfcLiveMigration::$vmsData['vms'][$vm];
        $pmd = RfcLiveMigration::$vmsData['pms'][$pm];
        
        //Verify networks
        foreach ($vmd['networks'] as $net) {
            $resp = array_search($net, $pmd['networks']);
            if ($resp === false) return false;
        }
        
        //Verify datastore
        foreach ($vmd['datastores'] as $ds) {
            $resp = array_search($ds, $pmd['datastores']);
            if ($resp === false) return false;
        }
        
        return true;
    }
}
RulesFreeOfContext::add('RfcLiveMigration');