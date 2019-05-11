<?php
require_once "libs/vmwarephp/Bootstrap.php";
define('FNVM', "data/vms.json");
define('FNPM', "data/pms.json");
class HelperVmData
{
    static private $data = null;
    static private $fmt_log = "%s  | %s/%s | ETA: %ss | Spend: %ss | %s \n";


    
    static public function getVmsData($max = null, $vmsFilter = []) {
        ini_set('xdebug.max_nesting_level', 2000);
        
        if (!file_exists(FNVM)) HelperVmData::loadVMs($vmware);
        if (!file_exists(FNPM)) HelperVmData::loadPMs($vmware);
        
        $pms = json_decode(file(FNPM) [0], true);
        $vms = json_decode(file(FNVM) [0], true);
        
        if (!empty($vmsFilter)) {
            foreach ($vms as $vm) {
                if (!isset($vmsFilter[$vm['name']])) unset($vms[$vm['name']]);
            }
        }

        //Block used for tests
        if (!is_null($max)) {
            $i = 0;
            foreach ($vms as $vm) {
                if ($i++ >= $max) unset($vms[$vm['name']]);
            }
        }
        
        //Cleaning unconsistent data
        foreach ($vms as $vm => $vmData) {
            $pm = $vmData['host'];
            foreach ($vmData['networks'] as $idx => $net) {
                if (!in_array($net, $pms[$pm]['networks'])) {
                    unset($vms[$vm]['networks'][$idx]);
                }
            }
            
            foreach ($vmData['datastores'] as $idx => $store) {
                if (!in_array($store, $pms[$pm]['datastores'])) {
                    unset($vms[$vm]['datastores'][$idx]);
                }
            }
        }
        
        $vmsData = ['pms' => $pms, 'vms' => $vms];
        
        //Cache::$cache->set('HelperVmData', $vmsData);
        HelperVmData::$data = $vmsData;
        return $vmsData;
    }
    
    static public function getRealCvmp($max = null, $vmsFilter = []) {
        $cvmp = [];
        $data = HelperVmData::getVmsData($max,$vmsFilter);
        foreach ($data['vms'] as $vm) {
            Cvmp::addVm($cvmp, $vm['name'], $vm['host']);
        }
        return unserialize(serialize($cvmp));
    }
    
    private static function placementIsValid($vm, $pm) {
        
        //Verify networks
        foreach ($vm['networks'] as $net) {
            $resp = array_search($net, $pm['networks']);
            if ($resp === false) return false;
        }
        
        //Verify datastore
        foreach ($vm['datastores'] as $ds) {
            $resp = array_search($ds, $pm['datastores']);
            if ($resp === false) return false;
        }
        
        return true;
    }
    
    private static function loadVMs($vmware) {
        
        //https://www.vmware.com/support/developer/vc-sdk/visdk41pubs/ApiReference/vim.VirtualMachine.html
        //http://pubs.vmware.com/vsphere-60/index.jsp?topic=/com.vmware.wssdk.apiref.doc/index.html&single=true&__utma=207178772.1811249502.1438681066.1438681066.1438681066.1&__utmb=207178772.0.10.1438681066&__utmc=207178772&__utmx=-&__utmz=207178772.1438681066.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)&__utmv=-&__utmk=104578819
        //'name', 'summary','network','datastore', 'config'
        $virtualMachines = array();
        $vms = array();
        echo date(DATE_RFC2822) . " - Inicio \n";
        $virtualMachines = $vmware->findAllManagedObjects('VirtualMachine', array('name', 'network', 'summary', 'config', 'runtime', 'datastore'));
        $num = count($virtualMachines);
        $count = 1;
        $start = time();
        foreach ($virtualMachines as $vm) {
            $newVM = array();
            $newVM['name'] = str_replace(':', '', $vm->name);
            
            $eta = intval((time() - $start) / $count) * ($num - $count + 1);
            $spend = time() - $start;
            echo sprintf(HelperVmData::$fmt_log, date(DATE_RFC2822), $num, $count++, $eta, $spend, $newVM['name']);
            
            if ($vm->runtime->powerState != 'poweredOn') continue;
            
            $newVM['host'] = $vm->runtime->host->name;
            $newVM['memory'] = $vm->config->hardware->memoryMB;
            $newVM['used_memory'] = $vm->summary->quickStats->hostMemoryUsage;
            $newVM['overhead_memory'] = $vm->summary->quickStats->consumedOverheadMemory;
            
            $newVM['uuid'] = $vm->config->uuid;
            
            $newVM['networks'] = array();
            foreach ($vm->network as $network) {
                if (!is_null($network)) {
                    $newVM['networks'][] = $network->name;
                }
            }
            
            $newVM['datastores'] = array();
            foreach ($vm->datastore as $datastore) $newVM['datastores'][] = $datastore->info->name;
            
            $vms[$newVM['name']] = $newVM;
        }
        
        $json = json_encode($vms);
        
        file_put_contents(FNVM, $json);
    }
    
    private static function loadPMs($vmware) {
        $physicalMachines = array();
        $pms = array();
        
        echo date(DATE_RFC2822) . " - Inicio \n";
        $physicalMachines = $vmware->findAllManagedObjects('HostSystem', array('name', 'network', 'datastore', 'hardware'));
        $num = count($physicalMachines);
        $count = 1;
        $start = time();
        foreach ($physicalMachines as $pm) {
            $newPM = array();
            $newPM['name'] = str_replace(':', '', $pm->name);
            
            $eta = intval((time() - $start) / $count) * ($num - $count + 1);
            $spend = time() - $start;
            echo sprintf(HelperVmData::$fmt_log, date(DATE_RFC2822), $num, $count++, $eta, $spend, $newPM['name']);
            
            $newPM['networks'] = array();
            foreach ($pm->network as $network) {
                $newPM['networks'][] = $network->name;
            }
            
            $newPM['datastores'] = array('ISOs');
            foreach ($pm->datastore as $datastore) {
                $newPM['datastores'][] = $datastore->info->name;
            }
            
            $newPM['memory'] = intval($pm->hardware->memorySize / (1024 * 1024));
            
            $pms[$newPM['name']] = $newPM;
        }
        $json = json_encode($pms);
        
        file_put_contents(FNPM, $json);
    }
}

