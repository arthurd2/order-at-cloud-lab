<?php
class HelperTexGraphPerc extends HelperTexGraph
{
    
    public function addCvmp(&$cvmp, $x, $line = 'line', $c = null) {
        
        $time = time() - Counter::$start;
        $cost = CostMigrations::getCost($cvmp);
        $cb = $cvmp[OC_TMP]['benefit']/$cost;
        $ben = $cvmp[OC_TMP]['benefit'];

        $avg = array_sum($cvmp[OC_TMP]['values']) / $cvmp['nvms'];
        $stdDev = stats_standard_deviation($cvmp[OC_TMP]['values']);
        $scenarios = Counter::$scenarios;
        $ndcvmp = Counter::$pareto;
        $top20 = $this->getDeltaTop20($cvmp);

        $fmt = '(%s,%s)';

        $e = &$this->graphic;
        $e['Time'][$line][]		= sprintf($fmt,$x,$this->_perc($time,$c['Time']));
        $e['CVMPs'][$line][]            = sprintf($fmt,$x,$this->_perc($scenarios,$c['CVMPs']));
        $e['CB'][$line][]		= sprintf($fmt,$x,$this->_perc($cb,$c['CB']));
        $e['Benefit'][$line][]          = sprintf($fmt,$x,$this->_perc($ben,$c['Benefit']));
        $e['Top 20 Lowest'][$line][]	= sprintf($fmt,$x,$this->_perc($top20,$c['Top 20 Lowest']));
        $e['Avg Benefit'][$line][]	= sprintf($fmt,$x,$this->_perc($avg,$c['Avg Benefit']));
        $e['Std Dev'][$line][]		= sprintf($fmt,$x,$this->_perc($stdDev,$c['Std Dev']));
        $e['Cost'][$line][]             = sprintf($fmt,$x,$this->_perc($cost,$c['Cost']));
        
        //$e['Non-Dominated CVMPs'][$line][]	= sprintf($vmt,$x,$ndcvmp);
    }
    
    function _perc($init,$comp){
        return ((($init/$comp))*100);
    }
}
