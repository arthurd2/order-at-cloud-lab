<?php

class CostMigrations extends Cost implements InterfaceCost{
	static $maxCost = 20;
	static function getCost(&$cvmp){
		$count = 1;
		foreach (Cache::$realCvmp['vmp'] as $vm => $pm) {
			if ($cvmp['vmp'][$vm] != $pm) {
				$count++;
			}
		}
		return $count;
	}
}
Costs::add('CostMigrations');