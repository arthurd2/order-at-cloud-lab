<?php
class RfcClusterCoherence extends Rule implements RuleFreeOfContext
{
    protected static $cluster = [];
    public static $clusters2pms = [];
    
    static public function load() {
        $clusters['labtrans'] = ['192.168.2.175'];
        $clusters['corporativo'] = ['192.168.2.205', '192.168.2.206', '192.168.2.217', '192.168.2.218', '192.168.2.219', '192.168.2.220' ];
        $clusters['R720'] = ['192.168.2.210', '192.168.2.211', '192.168.2.212', '192.168.2.213'];
        $clusters['R920'] = ['192.168.2.227', '192.168.2.228'];
        $clusters['INE'] = ['192.168.2.216', '192.168.2.225'];
        $clusters['Unasus'] = ['192.168.2.214', '192.168.2.215' ];
        
        foreach ($clusters as $name => $servers) {
            foreach ($servers as $pm) {
                RfcClusterCoherence::$clusters2pms[$name][] = $pm;
                RfcClusterCoherence::$cluster[$pm] = $name;
            }
        }
    }
    static function isAllowed(&$vm, &$pm) {
        $pmOrig = Cache::$realCvmp['vmp'][$vm];
        return (RfcClusterCoherence::$cluster[$pmOrig] == RfcClusterCoherence::$cluster[$pm]);
    }
}
RulesFreeOfContext::add('RfcClusterCoherence');
