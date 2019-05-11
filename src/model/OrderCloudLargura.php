<?php
class OrderCloudLargura extends OrderCloud
{
    public $monoSearch = true;

    public function generateCVMP($scenarioBase, $worstVm)
    {

        $scenarios   = [];
        $maxScenario = [];
        $scenario    = $scenarioBase;
        //$pm       = $scenario['vmp'][$vm];
        //Cvmp::removeVm($scenario, $vm);
        //$pms = $scenario['pmp'];
        //unset($pms[$pm]);
        $pms  = array_keys($scenario['pmp']);
        $vms  = array_keys($scenario['vmp']);
        $eval = -1;

        foreach ($vms as $vm) {
            Cvmp::removeVm($scenario, $vm);
            foreach ($pms as $pm) {
                //Avoid to try the same PM
                if ($scenarioBase['vmp'][$vm] == $pm) {
                    continue;
                }

                if (RulesFreeOfContext::isAllowed($vm, $pm)) {
                    $newScenario = $scenario;
                    Cvmp::addVm($newScenario, $vm, $pm);
                    if (RulesSensitiveToTheContext::isAllowed($newScenario)) {
                        $scenarios[] = $newScenario;
                    }
                }
            }
            Cvmp::addVm($scenario, $vm, $scenarioBase['vmp'][$vm]);
        }

        foreach ($scenarios as $sce) {
            if ($this->isNonDominanted($scenarioBase, $sce)) {
                Counter::$scenarios++;
                if ($this->monoSearch) {
                    if ($sce[OC_TMP]['values'][$worstVm] > $eval) {
                        $eval           = $sce[OC_TMP]['values'][$worstVm];
                        $maxScenario[0] = $sce;
                    }
                } else {
                    if ($sce[OC_TMP]['values'][$worstVm] > $scenarioBase[OC_TMP]['values'][$worstVm]) {
                        $maxScenario[] = $sce;
                    }
                }
            }
        }
        return $maxScenario;
    }
}
