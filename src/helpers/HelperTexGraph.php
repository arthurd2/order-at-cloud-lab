<?php
class HelperTexGraph
{
    public function __construct($name) {
        $this->name = $name;
        $this->graphic = [];
    }
    
    public function addCvmp(&$cvmp, $x, $line = 'line', $c = null ) {

        $time = time() - Counter::$start;
        $mtime = microtime(true) - Counter::$mstart;
        $fmt = ' (%s,%s) ';
        $e = &$this->graphic;
        
        //error_log(PHP_EOL.print_r($cvmp[OC_TMP],true).PHP_EOL);
        $apr = Approximation::getAveragePlacementRate($cvmp);
        $app = floor(($apr/$cvmp['npms'])*100);
        $e['Avg Place. Rate'][$line][]      = sprintf($fmt,$x,$apr);
        $e['Avg Place. Perc'][$line][]      = sprintf($fmt,$x,$app);

        if(!isset($cvmp[OC_TMP])) return;

        $cost = CostMigrations::getCost($cvmp);
        $cb = $cvmp[OC_TMP]['benefit']/$cost;
        $ben = $cvmp[OC_TMP]['benefit'];

        $avg = array_sum($cvmp[OC_TMP]['values']) / $cvmp['nvms'];
        //$stdDev = stats_standard_deviation($cvmp[OC_TMP]['values']);
        $scenarios = Counter::$scenarios;
        $ndcvmp = Counter::$pareto;
        $top20 = $this->getDeltaTop20($cvmp);
        $degraded = $this->getDegradedPlacements($cvmp);

        //$rules = $this->calledRules();

        
        //$e['Called Rules'][$line][]         = sprintf($fmt,$x,$rules);
        $e['Degraded Placements'][$line][]  = sprintf($fmt,$x,$degraded);
        
        $e['Time'][$line][]                 = sprintf($fmt,$x,$time);
        $e['MicroSeconds'][$line][]         = sprintf($fmt,$x,$mtime);
        $e['CVMPs'][$line][]                = sprintf($fmt,$x,$scenarios);
        $e['CB'][$line][]                   = sprintf($fmt,$x,$cb);
        $e['Benefit'][$line][]              = sprintf($fmt,$x,$ben);
        $e['Top 20 Lowest'][$line][]        = sprintf($fmt,$x,$top20);
        $e['Avg Benefit'][$line][]          = sprintf($fmt,$x,$avg);
        //$e['Std Dev'][$line][]              = sprintf($fmt,$x,$stdDev);
        $e['Cost'][$line][]                 = sprintf($fmt,$x,$cost);
        
        //$e['Non-Dominated CVMPs'][$line][]	= "($size,$ndcvmp)";
    }

    public function getDegradedPlacements(&$cvmp)
    {
        $realEval = &Cache::$realCvmp[OC_TMP]['values'];
        $bestEval = &$cvmp[OC_TMP]['values'];
        $ct = 0;
        foreach ($realEval as $vm => &$value) {
            $ct += $value > $bestEval[$vm] ? 1 :0;
        }
        return $ct;
    }
    
    public function calledRules() {
        $sum = 0;
        foreach(HandlerSingleton::$counter as $class){
            $sum += $class['total'];
        }
        return $sum;
    }
    public function finish($Xs = []) {
    	$return = sprintf($this->fmt_header,$this->name);
        
        $isComented = (empty($Xs))? '%' : '';

    	foreach ($this->graphic as $name => $lines) {
    			$return .= sprintf($this->fmt_header_graphic,$name,$isComented,implode(',', $Xs));
    	    	foreach ($lines as $lineName => $coord) {
    	    		$return .= sprintf($this->fmt_coord,implode('', $coord),"$name - $lineName");
    	    	}
    	    	$return .= sprintf($this->fmt_bottom_graphic,implode(',', array_keys($lines)));
    	}
    	$return .= sprintf($this->fmt_bottom,$this->name);
    	return $return;
    }
    
    protected $fmt_header = "\n%%TODO -------------------------- %s \n\\begin{figure*}\n\\newcommand{\\tamanho}{0.5}";
    protected $fmt_bottom = "\n\\caption{%s}\n\\label{fig:results}\n\\end{figure*}";
    
    protected $fmt_coord = "\n\\addplot  plot coordinates {  %s  }; %% %s\n";

    protected $fmt_header_graphic = "\n\\begin{tikzpicture}\n\\begin{axis}[
    %%title=,
    width=\\tamanho\linewidth,
    x tick label style={/pgf/number format/1000 sep=},
    ylabel=%s,
    ylabel near ticks,
    %ssymbolic x coords={%s},
    legend style={anchor=center,at={(0.5,0.5)}},
    ]\n";

    protected $fmt_bottom_graphic = "%%\\legend{%s}\n\\end{axis}\n\\end{tikzpicture}";

    public function getDeltaTop20(&$cvmp) {
        //error_log(PHP_EOL.print_r($cvmp[OC_TMP]['values'],true).PHP_EOL);
        //error_log(PHP_EOL.print_r(Cache::$realCvmp[OC_TMP]['values'],true).PHP_EOL);
    	$realEval = Cache::$realCvmp[OC_TMP]['values'];
    	$bestEval = &$cvmp[OC_TMP]['values'];
    	asort($realEval,SORT_NUMERIC);
    	$ct = 0;
    	$sum = 0;
    	foreach ($realEval as $vm => $value) {
    		$ct++;
    		$sum += $bestEval[$vm] - $value;
            //error_log(PHP_EOL.sprintf('vm(%s) base(%s) target(%s)',$vm,$value,$bestEval[$vm] ).PHP_EOL);
    		if ($ct >= 20) break;
    	}
    	return $sum;
    }
}
