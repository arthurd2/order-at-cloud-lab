<?php
use PHPUnit\Framework\TestCase;
class OrderCloudPTTest extends TestCase
{
    protected $sizes = [350, 400, 450, 500, 550, 600];
    
    public function setUp() {
        //if (extension_loaded('Xdebug')) $this->markTestSkipped('Please disable Xdebug before executing these tests. Skipping tests.');
        if (!class_exists('Thread'))  $this->markTestSkipped('Please enable ZTS and Thread before executing these tests. Skipping tests.');
        foreach (Costs::getClasses() as $class) Costs::del($class);
        foreach (Qualifiers::getClasses() as $class) Qualifiers::del($class);
        foreach (RulesFreeOfContext::getClasses() as $class) RulesFreeOfContext::del($class);
        foreach (RulesSensitiveToTheContext::getClasses() as $class) RulesSensitiveToTheContext::del($class);
        
        Costs::add('CostMigrations');
        CostMigrations::$maxCost = 20;
        
        RulesFreeOfContext::add('RfcLiveMigration');
        RulesFreeOfContext::add('RfcClusterCoherence');
        RulesSensitiveToTheContext::add('RscMemoryAvailability');
        RulesSensitiveToTheContext::add('RscMaxCost');
        
        Qualifiers::add('QUAconsolidatePm');
        Qualifiers::add('QuaDistributeServices');
        Qualifiers::add('QuaDistributeStoragePool');
    }   
    
    /**
     * @ depends xxxx
     */
    public function testCoherencebetweenPthreadsAndRecuglar() {
        $maxs = [10];
        $sizes = [350];
        $ptSwitch = [false,true];
        
        //$g = new HelperTexGraph("Varying Max Migrations");
        
        foreach ($maxs as $max) {
            CostMigrations::$maxCost = $max;
            foreach ($sizes as $size) {
                foreach ($ptSwitch as $pThreadFlag ) {
                    Counter::reset();
                    $realCvmp = HelperVmData::getRealCvmp($size);
                    $oc = new OrderCloudPT($realCvmp) ;
                    $oc->threadFlag = $pThreadFlag ;
                    $cvmp = $oc->organize($realCvmp);
                    $r[] = ['cvmp' => $cvmp, '#scen' => Counter::$scenarios];
                    //TODO Verificar pq o VALUES esta voltando apagado
                    //$g->addCvmp($bestCvmp, $size, "Max. $max Migrations");
                    Counter::stats(__FUNCTION__);
                }
            }
        }
        
        //TODO colocar o calculo automatico das porcentagens
        //error_log($g->finish());
        $this->assertEquals($r[0]['cvmp'][OC_TMP]['cost'], $r[1]['cvmp'][OC_TMP]['cost'], '# of Migrations !match');
        $this->assertEquals($r[0]['cvmp'][OC_TMP]['cb'], $r[1]['cvmp'][OC_TMP]['cb'], '# of Cost-Benefit !match');
        $this->assertEquals($r[0]['cvmp'][OC_TMP]['values'], $r[1]['cvmp'][OC_TMP]['values']);
        $this->assertEquals($r[0]['#scen'],$r[1]['#scen'] , '# of Scenarios !match');
    }
    
}
