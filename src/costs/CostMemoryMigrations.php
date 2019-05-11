<?php

class CostMemoryMigrations extends Cost implements InterfaceCost{
	private static $vmsData = [];

	static public function load(){
		$this->vmsData = HelperVmData::getVmsData();
	}

	static function getCost(&$cvmp){

		$count = 1;
		foreach (Cache::$realCvmp['vmp'] as $vm => $pm) {
			if ($cvmp['vmp'][$vm] != $pm) {
				$count += $this->vmsData['vms'][$vm]['used_memory'];
			}
		}
		return $count;
	}
}
Costs::add('CostMemoryMigrations');