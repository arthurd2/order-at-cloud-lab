<?php
use PHPUnit\Framework\TestCase;
/**
 * @backupGlobals disabled
 */
class OCOptimisationTest extends TestCase
{
    protected $sizes;
    
    public function setUp() {
        if (extension_loaded('Xdebug')) $this->markTestSkipped('Please disable Xdebug before executing these tests. Skipping tests.');
        foreach (Costs::getClasses() as $class) Costs::del($class);
        foreach (Qualifiers::getClasses() as $class) Qualifiers::del($class);
        foreach (RulesFreeOfContext::getClasses() as $class) RulesFreeOfContext::del($class);
        foreach (RulesSensitiveToTheContext::getClasses() as $class) RulesSensitiveToTheContext::del($class);
        
        $this->sizes = [350, 400, 450, 500, 550, 600];
        
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
    
    public function testCountClusterVMs() {
        
        $this->markTestIncomplete(__FUNCTION__);
        RfcClusterCoherence::load();
        $realCvmp = HelperVmData::getRealCvmp();
        
        foreach (RfcClusterCoherence::$clusters2pms as $cluster => $pms) {
            $ct = 0;
            foreach ($pms as $pm) {
                $ct+= count($realCvmp['pmp'][$pm]);
            }
            error_log($cluster . ": " . $ct . PHP_EOL);
        }
    }
    
    public function testCheckMethodsCoherence() {
        $maxs = [20];
        $this->sizes = [400];
        $strategies = ['oc','oc-exp', 'oc-cost-filter'];

        
        $results = [];
        
        foreach ($maxs as $max) {
            CostMigrations::$maxCost = $max;
            foreach ($this->sizes as $size) {
                foreach ($strategies as $method) {
                    Counter::reset();
                    $realCvmp = HelperVmData::getRealCvmp($size);
                    
                    switch ($method) {
                        case 'oc':
                            $oc = new OrderCloud($realCvmp);
                            break;

                        case 'oc-exp':
                            $oc = new OrderCloudExp($realCvmp);
                            break;
                        //Filtro de custo final que deveria melhorar a performance sem alterar o resultado
                        case 'oc-cost-filter':
                            $oc = new OrderCloudExp($realCvmp);
                            $oc->costFinalFilter = true;
                            break;
                        default:
                            throw new Exception("method !know");
                            break;
                    }
                    $bestCvmp = $oc->organize($realCvmp);
                    $results[$method]["$size-$max"]['cb'] = $bestCvmp[OC_TMP]['cb'];
                    $results[$method]["$size-$max"]['benefit'] = $bestCvmp[OC_TMP]['benefit'];
                    $results[$method]["$size-$max"]['cost'] = $bestCvmp[OC_TMP]['cost'];
                    
                    //$g->addCvmp($bestCvmp, $size, "Max. $max Migrations");
                    
                    Counter::stats(__FUNCTION__);
                }
            }
        }
        
        $oc = $results['oc'];
        unset($results['oc']);
        foreach ($results as $method => $tests) {
            foreach ($tests as $tname => $measures) {
                foreach ($measures as $key => $value) {
                    $this->assertEquals($oc[$tname][$key], $value, "Method($method) - Test($tname) - Result($key)");
                }
            }
        }
    }
    
    public function testTempoFixo120() {
        $this->markTestIncomplete(__FUNCTION__);
        $switchs = [false, true];
        $g = new HelperTexGraph("Comparison Varying Time and Cost Threshold");
        
        foreach ($switchs as $timeBased) {
            if ($timeBased) {
                RulesFreeOfContext::add('RfcTime');
                RfcTime::$maxTime = 120;
                RulesSensitiveToTheContext::del('RscMaxCost');
                $line = 'Time Based';
            } 
            else {
                RulesFreeOfContext::del('RfcTime');
                RulesSensitiveToTheContext::add('RscMaxCost');
                $line = 'Migration Based';
            }
            
            foreach ($this->sizes as $size) {
                Counter::reset();
                $realCvmp = HelperVmData::getRealCvmp($size);
                $oc = new OrderCloudExp($realCvmp);
                $bestCvmp = $oc->organize($realCvmp);
                
                $g->addCvmp($bestCvmp, $size, $line);
                
                Counter::stats(__FUNCTION__);
            }
        }
        error_log($g->finish());
        $this->assertTrue(True);
    }
    
    public function testSearchMethods() {
        $this->markTestIncomplete(__FUNCTION__);
        $methods = [false, true];
        $g = new HelperTexGraph("Comparison Deadlock-Safe Methods");
        foreach ($methods as $method) {
            foreach ($this->sizes as $size) {
                Counter::reset();
                $realCvmp = HelperVmData::getRealCvmp($size);
                $oc = ($method) ? new OrderCloudExp($realCvmp) : new OrderCloudWide($realCvmp);
                $bestCvmp = $oc->organize($realCvmp);
                
                $g->addCvmp($bestCvmp, $size, $method);
                
                Counter::stats(__FUNCTION__);
            }
        }
        error_log($g->finish());
        $this->assertTrue(true);
    }

    public function testVmsVsApr() {
        $this->markTestIncomplete(__FUNCTION__); 
    }
}
