<?php
use PHPUnit\Framework\TestCase;
class envTests extends TestCase {

	function testEnvVars(){
		$neededEnvVars[] = 'VMHOST';
		$neededEnvVars[] = 'VMUSER';
		$neededEnvVars[] = 'VMPASS';

		foreach ($neededEnvVars as $value) {
			$this->assertFalse(empty(getenv($value)),"Env Var '$value' is not set.");
		}	
	}
}
