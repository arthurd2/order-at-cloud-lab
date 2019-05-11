<?php
class NSGA2
{
    public static $sortingAttrib;    

    public function __construct(&$currentCvmp,$debug = false) {
        $this->debug = $debug;
        Cache::$realCvmp = & $currentCvmp;
        $this->executeLoadClasses();
        if ($this->debug) {
            $this->handlerStatus();
        }
    }

    private function executeLoadClasses() {
        foreach (Costs::getClasses() as $class) $class::load();
        foreach (Qualifiers::getClasses() as $class) $class::load();
        foreach (RulesFreeOfContext::getClasses() as $class) $class::load();
        foreach (RulesSensitiveToTheContext::getClasses() as $class) $class::load();
    }

    public function organize(&$scenario) {
        //Generate initial population
        error_log('Initiating Parents Generation');
        $parents = $this->generateParents($scenario);

        
        error_log('Initial population: ' . count($parents));
        
        //enquanto nao parar, gera mais geracoes
        $offspring = [$scenario];
        $g = 1;
        while (NSGA2::noOffspring($parents, $offspring)) {
            error_log('Generation: '.$g++.' with '.(count($parents)+count($offspring)));
            list($parents,$offspring) = $this->generation($parents, $offspring);
            //$parents = $tmp[0];
            //$offspring  = $tmp[1];
            //error_log(print_r($parents,true));
            //error_log(print_r($offspring,true));
        }
        
        //Seleciona melhor scenario
        $generations = array_merge($parents, $offspring);
        return $this->getCvmpWithMaxCostBenefit($generations);
    }
    
    public static function noOffspring(&$parents, &$offspring){
        //Stop if there are no more offspring
        return count($offspring) != 0 ;
    }



    public function generation($oldparents, $offspring) {
        //Set N
        //$N = ceil((count($oldparents) + count($offspring)) / 2);
        $N = count($oldparents);
        
        //Create Fronts
        error_log('- Fast Nondominated Sort');
        $population = array_merge($oldparents, $offspring);
        error_log(PHP_EOL.count($oldparents).' - '.count($offspring).PHP_EOL);
        $fronts = $this->fastNonDominatedSort($population);
        
        //While  are not fill - Parents < N
        $parents = [];
        $frontNum = 0;


        
        //Related to N on NGSA2
        error_log('- Building Parents');
        while ((count($parents) + count($fronts[$frontNum]) < $N)) {
            $parents = array_merge($parents, $fronts[$frontNum]);
            $frontNum++;
        }
        
        //Sorting last Front_l
        $lastFront = &$fronts[$frontNum];
        error_log('- Crowding Distance Assignment');
        $this->crowdingDistanceAssignment($lastFront);
        
        //Filling the Parents
        $N2 = $N - count($parents);
        for ($i = 0; $i < $N2; $i++) {
            $parents[] = array_shift($lastFront);
        }
        
        //Generate new Offspring
        error_log('- Making New Offspring with #parents: '.count($parents));
        $offspring = $this->makeNewOffspring($parents);
        
        return [$parents, $offspring];
    }
    
    public function crowdingDistanceAssignment(&$scenarios){
        foreach ($scenarios as &$scenario){
            $scenario[OC_TMP][__CLASS__]['distance'] = 0;
            foreach ($scenario[OC_TMP]['values'] as $vm => $value) 
                $values[$vm][]=$value;
        }
        
        //For each Placement
        $vms = array_keys($scenarios[0]['vmp']);
        foreach ($vms as $vm) {
            $length = count($scenarios);

            //Sort by Objetive
            NSGA2::$sortingAttrib = $vm;
            usort($scenarios,'NSGA2::nsgaCmp');

            //Infinity distances for the edges
            $scenarios[0][OC_TMP][__CLASS__]['distance'] = PHP_INT_MAX;
            $scenarios[($length-1)][OC_TMP][__CLASS__]['distance'] = PHP_INT_MAX;
            
            //Finding distance between Max and Min
            $delta = min($values[$vm])-max($values[$vm]);
            $delta = $delta==0? 1:$delta;

            //Calculate relative distances
            //From the second to the last-1
            for ($i=1; $i < $length-1 ; $i++) { 
                //distance += (next-previous)/(max-min)
                $next = &$scenarios[$i+1];
                $previous = &$scenarios[$i-1];
                $me = &$scenarios[$i];
                
                $d = ($next[OC_TMP]['values'][$vm]-$previous[OC_TMP]['values'][$vm]) / $delta;
                $me[OC_TMP][__CLASS__]['distance'] += $d;
            }
        }
    }

    public static function nsgaCmp(&$a,&$b){
        $vm = NSGA2::$sortingAttrib;
        if ($a[OC_TMP]['values'][$vm] == $b[OC_TMP]['values'][$vm]) return 0;
        return ($a[OC_TMP]['values'][$vm] < $b[OC_TMP]['values'][$vm])? -1 : 1 ; 
    }


    public function fastNonDominatedSort(&$population){
        $fronts = [];
        error_log('-- Comparando Cenarios');
        foreach ($population as &$scenario1) {
            $scenario1[OC_TMP][__CLASS__]['dominatedBy'] = 0;
            $store = &$scenario1[OC_TMP][__CLASS__];
            $store['dominates'] = [];
            foreach ($population as &$scenario2) {
                if($this->isNonDominanted($scenario2,$scenario1))
                    $store['dominates'][] = &$scenario2;
                elseif ($this->isNonDominanted($scenario1,$scenario2))
                    $store['dominatedBy']++;
            }
            if ($store['dominatedBy'] == 0){
                $store['rank'] = 0;
                $fronts[0][] = &$scenario1;
            }
        }
        $f = 0;
        error_log('-- Criando os fronts');
        while(isset($fronts[$f])) {
            foreach ($fronts[$f] as &$scenario) {
                $store = &$scenario[OC_TMP][__CLASS__];
                foreach ($store['dominates'] as &$dominated) {
                    $dominated[OC_TMP][__CLASS__]['dominatedBy']--;
                    if($dominated[OC_TMP][__CLASS__]['dominatedBy'] == 0){
                        $dominated[OC_TMP][__CLASS__]['rank'] = $f+1;
                        $fronts[$f+1][] = &$dominated;
                    }
                }
            }
            $f++;
        }
        return $fronts;
    }

    public function generateScenarios($scenario, $vm) {
        
        //TODO prettify this
        $newCvmps = [];
        $pm = $scenario['vmp'][$vm];
        $baseCvmp = $scenario;
        Cvmp::removeVm($scenario, $vm);
        $pms = $scenario['pmp'];
        unset($pms[$pm]);
        $pms = array_keys($pms);
        
        foreach ($pms as $pm) {
            if (RulesFreeOfContext::isAllowed($vm, $pm)) {
                $newCvmp = $scenario;
                Cvmp::addVm($newCvmp, $vm, $pm);
                if (RulesSensitiveToTheContext::isAllowed($newCvmp) and $this->isNonDominanted($baseCvmp, $newCvmp)) {
                    $newCvmps[] = $newCvmp;
                }
            }
        }
        return $newCvmps;
    }
    
    public function getCvmpWithMaxCostBenefit(&$scenarios) {
        $cvmpMax = array_pop($scenarios);
        $cbMax = Qualifiers::getCostBenefit($cvmpMax);
        
        foreach ($scenarios as $cvmp) {
            $cb = Qualifiers::getCostBenefit($cvmp);
            if ($cb > $cbMax) {
                $cbMax = $cb;
                $cvmpMax = $cvmp;
            }
        }
        return $cvmpMax;
    }
    
    //TODO Melhorar aqui!
    public function makeNewOffspring(&$scenarios) {
        $offsprings = [];
        if (count($scenarios) == 0) return [];
        $nvms = count($scenarios[0]['vmp']);
        error_log('--- Gerando Offspring');
        foreach ($scenarios as &$scenario ) {
            $offspring = [];
            $ignoreVms = [];
            while (count($offspring) == 0 and (count($ignoreVms) < $nvms)) {
                $newLowVM = $this->selectLowerVm($scenario,$ignoreVms);
                $ignoreVms[$newLowVM] = $newLowVM;
                $offspring = $this->generateScenarios($scenario, $newLowVM);
            }
            //$offspring = $this->generateParents($scenario);
            $offsprings = array_merge($offsprings,$offspring);
        }
        return $offsprings;
    }
    
    public function generateParents(&$scenario){
        $parents = [];
        $vms = array_keys($scenario['vmp']);
        
        foreach ($vms as $vm) {
            $nondominated = $this->generateScenarios($scenario, $vm);
            if(empty($nondominated)) continue;
            $best = $this->getCvmpWithMaxCostBenefit($nondominated) ;
            $parents[] = &$best;
        }
        return $parents;
    }

    public function selectLowerVm(&$scenario,$ignoreVms) {
        $evalBase = Qualifiers::getEvaluation($scenario);
        $valueMin = PHP_INT_MAX;
        $vmMin = null;
        
        foreach ($evalBase as $vm => $value) {
            if ($value < $valueMin and !isset($ignoreVms[$vm])) {
                $valueMin = $value;
                $vmMin = $vm;
            }
        }
        if (is_null($vmMin)) 
            throw new Exception("Could not find lower VM because the lower value is grater than the biggest INT", 1);
        
        return $vmMin;
    }

    public function isNonDominanted(&$baseCvmp, &$candidateCvmp) {        
        $evalBase = Qualifiers::getEvaluation($baseCvmp);
        $evalCand = Qualifiers::getEvaluation($candidateCvmp);
        $count = 0;
        foreach ($evalBase as $vm => $value) {
            $count+= $evalCand[$vm] - $value;
            if ($value > $evalCand[$vm]) 
                return false;
            
        }
        
        return  ($count > 0) ; //0 mens they are equal
    }
}
