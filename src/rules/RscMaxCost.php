<?php
class RscMaxCost extends Rule implements RuleSensitiveToTheContext
{
    static function isAllowed(&$cvmp) {
        return (Costs::getCost($cvmp) <= Costs::getMaxCost()) ;
    }
}
RulesSensitiveToTheContext::add('RscMaxCost');