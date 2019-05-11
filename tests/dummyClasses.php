<?php
class Counter
{
    static public $scenarios = 0;
    static public $pareto = 0;
    static public $leaf = 0;
    static public $start = 1;
    static public $mstart = 1;
    static public $counters = [];
    
    static function reset() {
        Counter::$scenarios = 0;
        Counter::$pareto = 0;
        Counter::$leaf = 0;
        Counter::$start = time()-1;
        Counter::$mstart = microtime(true);
        Counter::$counters = [];
    }
    static function stats($prefix = '') {
        $mdelta = (microtime(true)- Counter::$mstart);
        $delta = (time() - Counter::$start);
        
        
        if (!isset(Counter::$counters[$prefix])) Counter::$counters[$prefix] = 0;
        Counter::$counters[$prefix]++;
        $line = sprintf('%% %s - %s(%s)- Scenarios(%s) - ND(%s) - Leafs(%s) - Time(%s) - CVMP/sec(%s) - mTime(%s)', date(DATE_RFC2822), $prefix, Counter::$counters[$prefix], Counter::$scenarios, Counter::$pareto, Counter::$leaf, $delta, Counter::$scenarios / $delta,$mdelta);
        error_log($line);
    }
}
