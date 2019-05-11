<?php
use PHPUnit\Framework\TestCase;
class GerarScenariosTest extends TestCase
{

    public function testGeraSenarioName() {
        $nvms = 10;
        $npms = 10;
        $apr = 0.5;

        list($places, $r_vm, $r_pm) = Scenario::geraPlacements($apr,$nvms,$npms);
        $this->assertEquals($nvms,count($places),"Numero de VMs esta errado.");

        $total = 0;
        foreach ($places as $value)
            $total += count($value);
        
        $this->assertEquals($npms*$apr*$nvms,$total,"Numero final de estados esta errado.");
        $this->assertEquals(array_sum($r_vm),$total,"Numero final de estados diferente do r_vm.");
        $this->assertEquals(array_sum($r_pm),$total,"Numero final de estados diferente do r_pm.");
    }    

    public function testGeraSenarios() {
        $nvms = array(5,10);
        $npms = array(10,20,30);
        $apr = 0.5;

        $scenarios = Scenario::geraScenarios($apr,$nvms,$npms);
        $this->assertEquals(count($nvms)*count($npms),count($scenarios),"Numero de VMs esta errado.");
    }

    public function testbuildScenarioByPlacements(){
        $rvm = array('A'=>2,'B'=>2,'C'=>2,'D'=>3);
        $rpm = array('1'=>2,'2'=>4,'3'=>3);
        $placements = array(
            array('A:2', 'A:3'), 
            array('B:1', 'B:2'), 
            array('C:2', 'C:3'), 
            array('D:1', 'D:2', 'D:3')
            );
        
        $scenario = Scenario::buildScenarioByPlacements($placements);
        $this->assertEquals(count($rvm),$scenario['nvms'],'Number of VM are not the same');
        $this->assertEquals(count($rpm),$scenario['npms'],'Number of PM are not the same');
        $this->assertEquals($rvm,$scenario['rvm'],'VM report is not the same');
        $this->assertEquals($rpm,$scenario['rpm'],'PM report is not the same');
        $this->assertEquals(array_keys($rvm),array_keys($scenario['placements']),'Placements not equal');
        $this->assertEquals(array_values($placements),array_values($scenario['placements']),'Placements not equal');
    }


    /**
     * @depends testbuildScenarioByPlacements
     */
    public function testJsonToScenario(){
        $placements = array(
            array('A:2', 'A:3'), 
            array('B:1', 'B:2'), 
            array('C:2', 'C:3'), 
            array('D:1', 'D:2', 'D:3')
            );
        
        $scenario1 = Scenario::buildScenarioByPlacements($placements);
        $json = Scenario::toDataTableJSON($scenario1);
        $scenario2 = Scenario::getScenarioFromJSON($json);

        $this->assertEquals($scenario1['nvms'],$scenario2['nvms'],'VM # !match');
        $this->assertEquals($scenario1['npms'],$scenario2['npms'],'PM # !match');

        foreach ($scenario1['rvm'] as $key => $value) 
            $this->assertEquals($value,$scenario2['rvm'][$key],"# of placements of $key !match");

         foreach ($scenario1['rpm'] as $key => $value)
            $this->assertEquals($value,$scenario2['rpm'][$key],"# of placements of $key !match");
 
    }

}
