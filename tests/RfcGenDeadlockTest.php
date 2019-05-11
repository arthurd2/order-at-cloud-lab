<?php
use PHPUnit\Framework\TestCase;
/**
 * RfcGenDeadlockTest
 *
 * @group group
 */
class RfcGenDeadlockTest extends TestCase
{

    public function test()
    {
        $vm = 'dummy';
        $any = 'any';
        $default = 'Labtrans_gateway_firewall';
    	RfcGenDeadlock::$firstVm = $vm;
    	$this->assertFalse(RfcGenDeadlock::isAllowed($vm,$any));
    	$this->assertTrue(RfcGenDeadlock::isAllowed($any,$any));
    	RfcGenDeadlock::load();
    	$this->assertFalse(RfcGenDeadlock::isAllowed($default,$any));
    	$this->assertTrue(RfcGenDeadlock::isAllowed($any,$any));
    	
    }
}
