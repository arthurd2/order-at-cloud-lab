<?php
class RfcGenDeadlock extends Rule implements RuleFreeOfContext
{
	static $firstVm;

	static function load(){
		//TODO Avaliar sob demanda.
		RfcGenDeadlock::$firstVm = 'Labtrans_gateway_firewall';
		//RfcGenDeadlock::$firstVm = 'Labtrans_stilog-svnserver';
	}

    static function isAllowed(&$vm, &$pm) {
        return !(RfcGenDeadlock::$firstVm == $vm);
    }
}
RulesFreeOfContext::add('RfcGenDeadlock');