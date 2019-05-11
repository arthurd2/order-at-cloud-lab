<?php
use PHPUnit\Framework\TestCase;
class HelperVmDataTest extends TestCase
{
    
    public function testVmDataSet() {
        $data = HelperVmData::getVmsData();
        foreach ($data['vms'] as $vm => $vmData) {
            $pm = $vmData['host'];
            $this->assertTrue(isset($data['pms'][$pm]), "PM '$pm' !exists");
            
            $this->assertTrue(($vmData['memory']+$vmData['overhead_memory']) >= $vmData['used_memory'],"VM($vm) PM($pm): Set memory '$vmData[memory]' is greater then '$vmData[used_memory]'.");
            
            foreach ($vmData['networks'] as $net) {            	
                $this->assertTrue(in_array($net, $data['pms'][$pm]['networks']), "VM($vm) PM($pm): network not identified:\n" . print_r($vmData['networks'], true) . "\n--------\n" . print_r($data['pms'][$pm]['networks'], true));
            }

                       
            foreach ($vmData['datastores'] as $store) {
                $this->assertTrue(in_array($store, $data['pms'][$pm]['datastores']), "VM($vm) PM($pm): DS '$store' not identified in:\n\n" . print_r($data['pms'][$pm]['datastores'], true));
            }
        }
    }
    
    /**
     *
     * @depends testVmDataSet
     */
    public function testGetVmsPlacements() {
        $data = HelperVmData::getVmsData();
        $vms = $data['vms'];
        $places = HelperVmData::getRealCvmp();

        foreach ($places['vmp'] as $vm => $pm) {
        	$this->assertTrue(($vms[$vm]['host'] == $pm));
        	unset($vms[$vm]);
        }
        $this->assertEquals(0,count($vms));
    }
}
