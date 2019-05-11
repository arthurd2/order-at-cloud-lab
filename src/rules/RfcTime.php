<?php
class RfcTime extends Rule implements RuleFreeOfContext
{
    static public $maxTime = 120;
    static function isAllowed(&$vm, &$pm) {
        return (time()-Counter::$start <= RfcTime::$maxTime);
    }
}
RulesFreeOfContext::add('RfcLiveMigration');