<?php
class OrderCloudWide extends OrderCloudExp
{
    
        public function organize(&$baseCvmp, &$ignoreVMs = [], $isMainInteration = true) {
        $class = get_called_class();
        $pareto = [];
        if (count($ignoreVMs) >= $baseCvmp['nvms'] or $class::$stop) return $baseCvmp;
        
        //Select Lower VM from the Rank
        $lowVM = $this->selectLowerVm($baseCvmp, $ignoreVMs);

        //generateCVMP
        $cvmps = $this->generateCVMP($baseCvmp, $lowVM);
        
        //foreach Possible CVMP
        $ignoreVMs[$lowVM] = $lowVM;
        foreach ($cvmps as $key => $cvmp) {
            Counter::$scenarios++;
            if (!$class::$stop and $this->isNonDominanted($baseCvmp, $cvmp)) {
                Counter::$pareto++;
                $pareto[] = $this->organize($cvmp, $ignoreVMs, false);
            }
        }
        //Taking the lowVM putted before
        array_pop($ignoreVMs);
        if (empty($pareto)) Counter::$leaf++;
        
        $pareto[] = $baseCvmp;
        $sCvmp = $this->getCvmpWithMaxCostBenefit($pareto);
        
        if ($isMainInteration) {
            
            $class::updateIgnoreVMs($ignoreVMs,$sCvmp,$lowVM);
            $sCvmp = $this->organize($baseCvmp, $ignoreVMs, true);
        }
        return $sCvmp;
    }


    
    public function organizeee(&$initial, &$null = [], $isMainInteration = true) {
        $class = get_called_class();
        $newParetoFront = [];
        $paretoToExplore = [];
        
        $paretoFront = isset($initial['vmp'])? [$initial] : $initial ;

        //if (count($baseCvmp['ignoredVMs']) >= $baseCvmp['nvms'] or $class::$stop) return $baseCvmp;
        
        foreach ($paretoFront as $baseCvmp) {
            $foundNonDominated = false;
            
            //TODO Change to the generateCVMP
            if (!isset($baseCvmp['ignoredVMs'])) {
                $baseCvmp['ignoredVMs'] = [];
            }
            
            //Select Lower VM from the Rank
            $lowVM = $this->selectLowerVm($baseCvmp, $baseCvmp['ignoredVMs']);
            
            //generateCVMP
            $possibleCvmps = $this->generateCVMP($baseCvmp, $lowVM);
            foreach ($possibleCvmps as $cvmp) {
                Counter::$scenarios++;
                if ($this->isNonDominanted($baseCvmp, $cvmp)) {
                    $foundNonDominated = true;
                    Counter::$pareto++;
                    $cvmp['ignoredVMs'][$lowVM] = $lowVM;
                    $paretoToExplore[] = $cvmp;
                }
            }
            if (!$foundNonDominated) {
                Counter::$leaf++;
                $newParetoFront[] = $baseCvmp;
            }
        }

        if (!empty($paretoToExplore)) {
            error_log('.');
            $bestCvmp = $this->organize($paretoToExplore, $null, true);
            $newParetoFront[] = $bestCvmp;
        }

        $sCvmp = $this->getCvmpWithMaxCostBenefit($newParetoFront);
        
        return $sCvmp;
    }
}
