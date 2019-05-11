<?php

class CostOne extends Cost implements InterfaceCost{
	static function getCost(&$cvmp){
		return 1;
	}	
}
Costs::add('CostOne');