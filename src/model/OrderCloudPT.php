<?php
class OrderCloudPT extends OrderCloud
{
    public $threadFlag;
    
    public function organize(&$baseCvmp, &$ignoreVMs = [], $isMainInteration = true) {
        
        $class = get_called_class();
        $pareto = [];
        if (count($ignoreVMs) >= $baseCvmp['nvms'] or $class::$stop) return $baseCvmp;
        
        //Select Lower VM from the Rank
        $lowVM = $this->selectLowerVm($baseCvmp, $ignoreVMs);
        
        //generateCVMP
        $cvmps = $this->generateCVMP($baseCvmp, $lowVM);
        
        //foreach Possible CVMP
        $workers = [];
        $ignoreVMs[$lowVM] = $lowVM;

        foreach ($cvmps as $key => $cvmp) {
            Counter::$scenarios++;
            if (!$class::$stop and $this->isNonDominanted($baseCvmp, $cvmp)) {
                Counter::$pareto++;
                if ($isMainInteration and $this->threadFlag) $workers[] = new OCWorker($this, $cvmp, $ignoreVMs);
                else $pareto[] = $this->organize($cvmp, $ignoreVMs, false);
            }
        }
        
        //Manage the Threads
        if (!empty($workers)) {
            $pareto = $this->executeWorkers($workers);
        }
        
        //Taking the lowVM putted before
        array_pop($ignoreVMs);
        
        //Do nothing, just count.
        if (empty($pareto)) Counter::$leaf++;

        $pareto[] = $baseCvmp;

        $sCvmp = $this->getCvmpWithMaxCostBenefit($pareto);

        if ($isMainInteration) {
            
            $newLowVM = $this->selectLowerVm($sCvmp, $ignoreVMs);
            $ignoreVMs[$newLowVM] = $newLowVM;
           // error_log($sCvmp[OC_TMP]['cb'] . ' - ' . $newLowVM);
            $sCvmp = $this->organize($sCvmp, $ignoreVMs, true);
        }
        return $sCvmp;
    }
    
    function executeWorkers(&$workers) {
        $pareto = [];
        foreach ($workers as $worker) {
            $worker->start();
        }
        foreach ($workers as $w) {
            $w->join();
            $pareto[] = $w->result;
        }
        return $pareto;
    }
}

if (class_exists('Thread')) {
    class OCWorker extends Thread
    {
        public $result;
        public function __construct($oc, $cvmp, $ignoreVMs) {
            $this->realCvmp = Cache::$realCvmp;
            $this->baseCvmp = $cvmp;
            $this->oc = $oc;
            $this->ignoreVMs = $ignoreVMs;
            $this->result = null;
        }
        
        public function run() {

            Cache::$realCvmp = $this->realCvmp;
            $this->result = $this->oc->organize($this->baseCvmp, $this->ignoreVMs, false);
        }
    }
}
