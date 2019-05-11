<?php
use PHPUnit\Framework\TestListener;
class PHPUnitTestListener implements TestListener
{
    private $time;
    private $timeLimit = 0.1;
    
    public function startTest(PHPUnit\Framework\Test $test) {
        $this->time = microtime(true);
    }

    public function endTest(PHPUnit\Framework\Test $test, $time) {
        $fmt =  "\nTime: %s ms Name: %s ";
        $current = microtime(true);
        $took = $current - $this->time;
        
        if($took > $this->timeLimit ) 
            error_log(sprintf($fmt,$took,$test->getName(),$this->time,$current));
        
        
    }
    public function addError(PHPUnit\Framework\Test $test, Exception $e, $time) {    }
    public function addWarning(PHPUnit\Framework\Test $test, PHPUnit\Framework\Warning $e, $time) {    }
    public function addFailure(PHPUnit\Framework\Test $test, PHPUnit\Framework\AssertionFailedError $e, $time) {    }
    public function addIncompleteTest(PHPUnit\Framework\Test $test, Exception $e, $time) {    }
    public function addSkippedTest(PHPUnit\Framework\Test $test, Exception $e, $time) {    }
    public function startTestSuite(PHPUnit\Framework\TestSuite $suite) {    }
    public function endTestSuite(PHPUnit\Framework\TestSuite $suite) {    }
    public function addRiskyTest(PHPUnit\Framework\Test $test, Exception $e, $time) {    }
}
