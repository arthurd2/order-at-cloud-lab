<?php
use PHPUnit\Framework\TestCase;
/*
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class DocPaperTest extends TestCase
{
	protected $sizes;

	public function setUp() {
		if (extension_loaded('Xdebug')) $this->markTestSkipped('Please disable Xdebug before executing these tests. Skipping tests.');
		foreach (Costs::getClasses() as $class) Costs::del($class);
		foreach (Qualifiers::getClasses() as $class) Qualifiers::del($class);
		foreach (RulesFreeOfContext::getClasses() as $class) RulesFreeOfContext::del($class);
		foreach (RulesSensitiveToTheContext::getClasses() as $class) RulesSensitiveToTheContext::del($class);

		$this->sizes = [350, 400, 450, 500, 550, 600];

		Costs::add('CostMigrations');
		CostMigrations::$maxCost = 20;

		RulesFreeOfContext::add('RfcLiveMigration');
		RulesFreeOfContext::add('RfcClusterCoherence');
		RulesSensitiveToTheContext::add('RscMemoryAvailability');
		RulesSensitiveToTheContext::add('RscMaxCost');

		Qualifiers::add('QUAconsolidatePm');
		Qualifiers::add('QuaDistributeServices');
		Qualifiers::add('QuaDistributeStoragePool');
	}

	public function testOcVsLargura() {

		$this->markTestIncomplete(__FUNCTION__);
		$lines = ['OC Unlimited', 'larguraMono','larguraFull'];

		$xs = [6, 8, 10, 12, 14];
		$g = new HelperTexGraph(__FUNCTION__);

		foreach ($xs as $x) {
			CostMigrations::$maxCost = $x;

			foreach ($lines as $line) {

				Counter::reset();
				$allVms = HelperVmData::getRealCvmp();
				error_log(PHP_EOL.print_r($line,true).PHP_EOL);
				switch ($line) {

					case 'larguraFull':
						$oc = new OrderCloudLargura($allVms);
						$oc->monoSearch = false;
						break;

					case 'larguraMono':
						$oc = new OrderCloudLargura($allVms);
						break;

					case 'OC Unlimited':
						$oc = new OrderCloud($allVms);
						break;

					default:
						throw new Exception("method not found", 1);
						break;
				}

				$bestCvmp = $oc->organize($allVms);
				$g->addCvmp($bestCvmp, $x, $line);
				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(True);
	}

	public function testOptimalScenarios() {        
		$this->markTestIncomplete(__FUNCTION__);
		$lines = ['OC Unlimited', 'Optimal', 'Optimal CB'];

		$xs = [15, 16, 17, 18, 19, 20];
		$g = new HelperTexGraph("Getting the Best of the Best");

		foreach ($xs as $x) {
			$vms = $this->_getVmsByClusters(['corporativo']);
			foreach ($lines as $line) {
				Counter::reset();
				$allVms = HelperVmData::getRealCvmp(null, $vms);

				$cluster = ['192.168.2.218', '192.168.2.219', '192.168.2.220'];
				$realCvmp = [];
				$ct = 0;
				while ($ct <= $x) {
					$pm = $cluster[$ct % count($cluster) ];
					$vm = array_pop($allVms['pmp'][$pm]);
					Cvmp::addVm($realCvmp, $vm, $pm);
					$ct++;
				}


				//Sem limite de Custo
				RulesSensitiveToTheContext::del('RscMaxCost');
				switch ($line) {
					case 'Optimal':
						$oc = new OrderCloudExp($realCvmp);               
						//Sem considerar o Custo
						Costs::add('CostOne');

						//Desativando beneficios do OC
						$this->_optimalSetUp($oc);
						break;

					case 'Optimal CB':
						$oc = new OrderCloudExp($realCvmp);                        
						//Considerando o Custo
						Costs::add('CostMigrations');

						//Desativando beneficios do OC
						$this->_optimalSetUp($oc);

						break;
					case 'OC Unlimited':
						$oc = new OrderCloudExp($realCvmp);
						Costs::add('CostMigrations');
						break;

					default:
						throw new Exception("method not found", 1);
						break;
				}

				$bestCvmp = $oc->organize($realCvmp);
				$g->addCvmp($bestCvmp, $x, $line);
				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(True);
	}
	public function _optimalSetUp(&$oc) {

		//Usando Greatest Benefit
		//$oc->paretoFilter = false;
		//Liberar a exploracao sem ignoreset
		//$oc->useIgSetOnExploration = false;
		//Adicionar noIgSet somente se identificar um deadlock
		//$oc->ignoreVmsOnDemand = true;
		//Se houve uma alteração, limpa o IgSet final para tentar melhorar as VMs em deadlock
		//$oc->resetIG = true;

		//AntiDeadlock
		$oc->antiOptimalDeadlock = true;

		//desativo filtros de senario (Dom|GrB)
		$oc->disablePareto = true;

		//enabling GB
		//$oc->paretoFilter = false;

		//ativo igset de exploracao (default é true)
		$oc->useIgSetOnExploration = true;

		//desativo recursao final
		$oc->finalRecursion = false;
	}
	public function testCompareNsga2() {

		$this->markTestIncomplete(__FUNCTION__);
		$lines = ['nsga2noOffspring','oc'];

		$xs = [15, 16, 17, 18, 19, 20];
		$g = new HelperTexGraph("OC vs NSGA2");

		foreach ($xs as $x) {
			CostMigrations::$maxCost = $x;
			foreach ($lines as $line) {
				Counter::reset();
				$realScenario = HelperVmData::getRealCvmp();
				error_log(PHP_EOL."========================== $line - $x ".PHP_EOL);
				switch ($line) {
					case 'oc':
						$oc = new OrderCloud($realScenario);
						$bestScenario = $oc->organize($realScenario);
						break;

					case 'nsga2noOffspring':
						$nsga = new NSGA2($realScenario);
						$bestScenario = $nsga->organize($realScenario);                  
						break;

					default:
						throw new Exception("method not found", 1);
						break;
				}

				$g->addCvmp($bestScenario, $x, $line);
				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(True);
	}

	public function testVariacaoDoARP() {

		$this->markTestIncomplete(__FUNCTION__);

		$sizes = range(1, 607);

		$g = new HelperTexGraph(__FUNCTION__);
		foreach ($sizes as $size) {
			Counter::reset();
			$scenario = HelperVmData::getRealCvmp($size);
			Cache::$realCvmp = $scenario;
			foreach (RulesFreeOfContext::getClasses() as $class) $class::load();
			//die(print_r($scenario,true));
			$g->addCvmp($scenario, $size);
			Counter::stats(__FUNCTION__);
		}

		error_log($g->finish($sizes));
		$this->assertTrue(True);
	}



	public function testDistanceFromOptimalScenarios() {

		$this->markTestIncomplete(__FUNCTION__);

		$xs = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95];

		$cluster = 'R720';
		$vms = $this->_getVmsByClusters([$cluster]);

		//Dados do Optimo
		$o['Time'] = 16;
		$o['CVMPs'] = 11293;
		$o['CB'] = 1.3070652173913;
		$o['Benefit'] = 120.25;
		$o['Top 20 Lowest'] = 15;
		$o['Avg Benefit'] = 1.5162;
		$o['Std Dev'] = 1.06538;
		$o['Cost'] = 92;

		//Dados do Optimo CB
		$ocb['Time'] = 30;
		$ocb['CVMPs'] = 21949;
		$ocb['CB'] = 2.395;
		$ocb['Benefit'] = 59.875;
		$ocb['Top 20 Lowest'] = 7.75;
		$ocb['Avg Benefit'] = 1.1610294117647;
		$ocb['Std Dev'] = 0.74784472614913;
		$ocb['Cost'] = 25;

		//Dados do OC ilimitado
		$oci['Time'] = 3;
		$oci['CVMPs'] = 981;
		$oci['CB'] = 2.0390625;
		$oci['Benefit'] = 65.25;
		$oci['Top 20 Lowest'] = 9;
		$oci['Avg Benefit'] = 1.1926470588235;
		$oci['Std Dev'] = 0.75258206049936;
		$oci['Cost'] = 32;

		$g = new HelperTexGraphPerc("Distance between the optimal");
		foreach ($xs as $x) {
			Counter::reset();

			CostMigrations::$maxCost = floor((count($vms) / 100) * $x);

			$realCvmp = HelperVmData::getRealCvmp(null, $vms);

			$oc = new OrderCloudExp($realCvmp);

			$bestCvmp = $oc->organize($realCvmp);

			$g->addCvmp($bestCvmp, $x, 'From OC unlimited', $oci);
			$g->addCvmp($bestCvmp, $x, 'From Optimal', $o);
			$g->addCvmp($bestCvmp, $x, 'From Optimal CB', $ocb);

			Counter::stats(__FUNCTION__);
		}
		error_log($g->finish());
		$this->assertTrue(True);
	}

	public function testPerformance() {
		$this->markTestIncomplete(__FUNCTION__);
		$maxs = [5, 10, 20];
		$g = new HelperTexGraph("Varying Max Migrations");

		foreach ($maxs as $max) {
			CostMigrations::$maxCost = $max;
			foreach ($this->sizes as $size) {
				Counter::reset();
				$realCvmp = HelperVmData::getRealCvmp($size);
				$oc = new OrderCloudExp($realCvmp);
				$bestCvmp = $oc->organize($realCvmp);

				$g->addCvmp($bestCvmp, $size, "Max. $max Migrations");

				Counter::stats(__FUNCTION__);
			}
		}

		error_log($g->finish());
		$this->assertTrue(True);
	}

	public function testSelectionMethods() {
		$this->markTestIncomplete(__FUNCTION__);
		CostMigrations::$maxCost = 14;
		$this->sizes = []; 
		for ($i=350; $i <= 600 ; $i += 25){
			$this->sizes[] = $i;
		}

		$methods = ['higher', 'random', 'rank'];
		$g = new HelperTexGraph(__FUNCTION__);
		foreach ($methods as $method) {
			foreach ($this->sizes as $size) {
				Counter::reset();
				$realCvmp =  HelperVmData::getRealCvmp($size);
				$oc = new OrderCloudExp($realCvmp);
				$oc->selectionMethod = $method;
				$bestCvmp = $oc->organize($realCvmp);

				$g->addCvmp($bestCvmp, $size, $method);

				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(true);
	}

	public function testOrdemDasRegras() {
		$this->markTestIncomplete(__FUNCTION__);

		$methods = ['11', '12', '21', '22'];

		//$this->sizes = [400,500];
		CostMigrations::$maxCost = 25;

		$g = new HelperTexGraph("Changing Order of Rules");
		foreach ($methods as $method) {
			foreach ($this->sizes as $size) {
				//HandlerSingleton::$counter = [];
				Counter::reset();
				$this->_addRulesInOrder($method);
				$realCvmp = HelperVmData::getRealCvmp($size);
				$oc = new OrderCloudExp($realCvmp, true);

				$bestCvmp = $oc->organize($realCvmp);

				$g->addCvmp($bestCvmp, $size, $method);

				Counter::stats(__FUNCTION__);
				error_log(print_r(HandlerSingleton::$counter, true));
			}
		}
		error_log($g->finish());
		$this->assertTrue(true);
	}

	public function testDeadlockMethods() {
		$this->markTestIncomplete(__FUNCTION__);

		$methods = ['reset+nomigset', 'igset', 'nomigset'];

		CostMigrations::$maxCost = 20;
		$g = new HelperTexGraph("Anti-Deadlock Methods");
		foreach ($methods as $method) {
			foreach ($this->sizes as $size) {
				Counter::reset();
				$realCvmp = HelperVmData::getRealCvmp($size);
				$oc = new OrderCloudExp($realCvmp);
				$recursion = true;
				switch ($method) {
					case 'igset':

						// Normal method
						break;

					case 'nofigset':

						//No Final IgnoreSet
						$oc->finalRecursion = false;
						break;

					case 'igsod':

						// IgnoreSet On Demand
						$oc->ignoreVmsOnDemand = true;
						break;

					case 'nomigset':

						// No Middle IgnoreSet
						$oc->useIgnoreVms = false;
						break;

					case 'reset':

						// reset final IG if ND was found
						$oc->resetIG = true;
						break;

					case 'reset+nomigset':

						// No Middle IgnoreSet and reset final IG if ND was found
						$oc->useIgnoreVms = false;
						$oc->resetIG = true;
						break;

					default:
						throw new Exception("unknown method", 1);
						break;
				}
				$bestCvmp = $oc->organize($realCvmp);

				$g->addCvmp($bestCvmp, $size, $method);

				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(true);
	}

	public function testFiltersMethods() {

		$this->markTestIncomplete(__FUNCTION__);
		$paretoSwitch = [true, false];

		$sizes = [6, 8, 10, 12, 14];

		$g = new HelperTexGraph("Filters Methods");
		foreach ($paretoSwitch as $pareto) {
			foreach ($sizes as $size) {
				Counter::reset();
				CostMigrations::$maxCost = $size;
				$realCvmp = HelperVmData::getRealCvmp(600);
				$oc = new OrderCloudExp($realCvmp);
				$oc->paretoFilter = $pareto;
				$bestCvmp = $oc->organize($realCvmp);

				$g->addCvmp($bestCvmp, $size, "Pareto Filter($pareto)");

				Counter::stats(__FUNCTION__);
			}
		}
		error_log($g->finish());
		$this->assertTrue(True);
	}

	public function testGetVmsByClusters() {
		$this->markTestIncomplete(__FUNCTION__);

		$this->assertEquals(0, count($this->_getVmsByClusters(null)));
		$this->assertEquals(240, count($this->_getVmsByClusters('corporativo')));
		$this->assertEquals(240, count($this->_getVmsByClusters(['corporativo'])));
		$this->assertEquals(251, count($this->_getVmsByClusters(['corporativo', 'labtrans'])));
		$this->assertEquals(48, count($this->_getVmsByClusters(['Unasus', 'INE'])));
	}



	public function _getVmsByClusters($clusters) {

		if (is_null($clusters)) return [];

		if (!is_array($clusters)) $clusters = [$clusters];

		//Select Cluster os VMs
		RfcClusterCoherence::load();
		$realCvmp = HelperVmData::getRealCvmp();
		$vms = [];
		foreach (RfcClusterCoherence::$clusters2pms as $cluster => $pms) {
			foreach ($pms as $pm) {
				$vms[$cluster] = isset($vms[$cluster]) ? array_merge($vms[$cluster], $realCvmp['pmp'][$pm]) : $realCvmp['pmp'][$pm];
			}
		}
		$r = [];
		foreach ($clusters as $cluster) {
			$r = array_merge($r, $vms[$cluster]);
		}
		return $r;
	}

	public function _addRulesInOrder($order) {
		foreach (RulesFreeOfContext::getClasses() as $class) RulesFreeOfContext::del($class);
		foreach (RulesSensitiveToTheContext::getClasses() as $class) RulesSensitiveToTheContext::del($class);

		switch ($order[0]) {
			case '1':
				RulesFreeOfContext::add('RfcLiveMigration');
				RulesFreeOfContext::add('RfcClusterCoherence');
				break;

			case '2':
				RulesFreeOfContext::add('RfcClusterCoherence');
				RulesFreeOfContext::add('RfcLiveMigration');
				break;

			default:
				throw new Exception("wut1?", 1);

				break;
		}
		switch ($order[1]) {
			case '1':
				RulesSensitiveToTheContext::add('RscMemoryAvailability');
				RulesSensitiveToTheContext::add('RscMaxCost');
				break;

			case '2':
				RulesSensitiveToTheContext::add('RscMaxCost');
				RulesSensitiveToTheContext::add('RscMemoryAvailability');
				break;

			default:
				throw new Exception("wut2?", 1);

				break;
		}
	}
}
