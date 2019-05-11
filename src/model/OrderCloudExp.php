<?php
class OrderCloudExp extends OrderCloud
{
    public $selectionMethod = 'rank';
    public $paretoFilter = true;
    public $useIgSetOnExploration = true;
    public $ignoreVmsOnDemand = false;
    public $disablePareto = false;
    public $resetIG = false;
    public $costFinalFilter = false;
    public $finalRecursion = true;
    public $antiOptimalDeadlock = false;

    
    public function selectLowerVm(&$cvmp, &$ignoreVMs) {
        
        switch ($this->selectionMethod) {
            case 'rank':
                return parent::selectLowerVm($cvmp, $ignoreVMs);
                break;

            case 'higher':
                return $this->selectHigherVm($cvmp, $ignoreVMs);
                break;

            case 'random':
                $vms = $cvmp['vmp'];
                while (!empty($vms)) {
                    $vm = array_pop($vms);
                    if (!isset($ignoreVMs[$vm])) return $vm;
                    unset($vms[$vm]);
                }
                break;

            default:
                throw new Exception("I do not know this selection method");
                break;
            }
            
            throw new Exception("Selecting VM when ignoreVMs set is full", 1);
    }
    public function updateIgnoreVMs(&$ignoreVMs, &$sCvmp, $lowVM, &$pareto) {
        
        if (!$this->ignoreVmsOnDemand and  $this->resetIG and (count($pareto) > 1)) {
            $ignoreVMs = [];
            return;
        }
        $newLowVM = $this->selectLowerVm($sCvmp, $ignoreVMs);
        if ($this->ignoreVmsOnDemand) {
            if ($newLowVM == $lowVM) $ignoreVMs[$lowVM] = $lowVM;
            elseif ($this->resetIG) $ignoreVMs = [];
        }else{ $ignoreVMs[$newLowVM] = $newLowVM;}
    }
    public function isNonDominanted(&$baseCvmp, &$candidateCvmp) {
        $evalBase = Qualifiers::getEvaluation($baseCvmp);
        $evalCand = Qualifiers::getEvaluation($candidateCvmp);
        return ($this->paretoFilter) ? parent::isNonDominanted($baseCvmp, $candidateCvmp) : (array_sum($evalCand) > array_sum($evalBase));
    }
    
    public function selectHigherVm(&$cvmp, &$ignoreVMs) {
        $evalBase = Qualifiers::getEvaluation($cvmp);
        $ignore = array_flip($ignoreVMs);
        $valueMax = - 1;
        $vmMax = null;
        
        foreach ($evalBase as $vm => $value) {
            if (!isset($ignore[$vm]) and ($value > $valueMax)) {
                $valueMax = $value;
                $vmMax = $vm;
            }
        }
        if (is_null($vmMax)) {
            throw new Exception("Couldnt find higher VM because the hight value is greater than the smaller INT", 1);
        }
        return $vmMax;
    }
    
    public function organize(&$baseCvmp, &$ignoreVMs = [], $isMainInteration = null ) {
        if(is_null($isMainInteration)) $isMainInteration = $this->finalRecursion;
        $class = get_called_class();
        $pareto = [];
        
        
        $cvmps = [];

        //Utilizado nos teste de otimicidade, para evitar os deadlocks do optimo
        do{
            if (count($ignoreVMs) >= $baseCvmp['nvms']) return $baseCvmp;
            //Select Lower VM from the Rank
            $lowVM = $this->selectLowerVm($baseCvmp, $ignoreVMs);
        
            //generateCVMP
            $cvmps = $this->generateCVMP($baseCvmp, $lowVM);
        
            //foreach Possible CVMP
            $ignoreVMs[$lowVM] = $lowVM;
        
            if(!$this->antiOptimalDeadlock) break;
        }while(count($cvmps) == 0);
        foreach ($cvmps as $key => $cvmp) {
            Counter::$scenarios++;
            if ($this->disablePareto or $this->isNonDominanted($baseCvmp, $cvmp)) {
                Counter::$pareto++;
                if ($this->useIgSetOnExploration) $ig = & $ignoreVMs;
                else $ig = [];
                
                $pareto[] = $this->organize($cvmp, $ig, false);
            }
        }
        
        //Taking the lowVM putted before
        array_pop($ignoreVMs);
        if (empty($pareto)) Counter::$leaf++;
        
        $pareto[] = $baseCvmp;
        $sCvmp = $this->getCvmpWithMaxCostBenefit($pareto);
        
        //TODO Cuidado com isto aqui!!!
        $continue = !($this->costFinalFilter and isset($sCvmp[OC_TMP]['cost']) and ($sCvmp[OC_TMP]['cost'] >= Costs::getMaxCost()));
        
        if ($isMainInteration and $continue) {
             
            @$class::updateIgnoreVMs($ignoreVMs, $sCvmp, $lowVM, $pareto);
            
            $sCvmp = $this->organize($sCvmp, $ignoreVMs, true);
        }
        return $sCvmp;
    }
}
