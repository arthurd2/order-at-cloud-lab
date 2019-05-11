<?php
use PHPUnit\Framework\TestCase;
class ApproximationTest extends TestCase
{
    public function setUp() {
        
        foreach (Costs::getClasses() as $class) Costs::del($class);
        foreach (Qualifiers::getClasses() as $class) Qualifiers::del($class);
        foreach (RulesFreeOfContext::getClasses() as $class) RulesFreeOfContext::del($class);
        foreach (RulesSensitiveToTheContext::getClasses() as $class) RulesSensitiveToTheContext::del($class);
        
        RulesFreeOfContext::add('RfcLiveMigration');
        RulesFreeOfContext::add('RfcClusterCoherence');
    }

    public function testPossibilities() {
        $this->markTestIncomplete('Not yet implemented');
        $cvmp = HelperVmData::getRealCvmp(50);
        Cache::$realCvmp = &$cvmp;
        RfcLiveMigration::load();
        RfcClusterCoherence::load();
    }
}
