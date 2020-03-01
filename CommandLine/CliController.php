<?php
	namespace CommandLine;
	
	use Skyenet\Controller\Controller;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 22/02/2020
	 * Time: 4:18 pm
	 */

	class CliController extends Controller {
		protected array $availableModules = [
		];

		protected function discoverModules(): void {
			$allFiles = scandir(__DIR__.'/Modules');
			$allFiles = array_filter($allFiles, static function($value) {
				return stripos($value, '.php') !== false && $value !== 'CliModule.php';
			});

			foreach  ($allFiles AS $filename) {
				$class = substr($filename, 0, stripos($filename, '.php'));
				$className = "CommandLine\\Modules\\{$class}";

				if (!in_array(CliModule::class, class_parents($className), true)) {
					continue;
				}

				/** @var CliModule $className clean-up our IDE notice */
				$desc = $className::DESCRIPTION;
				$name = $className::MODULE_NAME;

				$this->availableModules[$name] = [$className, $desc];
			}
		}

		public function prepareForCli(): void {
			ob_end_flush();

			$longOpts = [
				'module:'
			];

			$this->discoverModules();

			$options = getopt('', $longOpts);
			if (!($options['module'] ?? null)) {
				echo 'A valid module must be set with the --module flag' . PHP_EOL;

				foreach ($this->availableModules AS $moduleName => $moduleArray) {
					$moduleName = str_replace(':', '', $moduleName);
					$text = $moduleArray[1];


					$moduleName = str_pad($moduleName, 43, ' ', STR_PAD_RIGHT);
					echo "\t--module={$moduleName}{$text}" . PHP_EOL;
				}

				return;
			}

			$module = strtolower($options['module']);
			if ($moduleArray = $this->availableModules[$module]) {
				$moduleClass = $moduleArray[0];

				/** @var CliModule $moduleInstance */
				$moduleInstance = new $moduleClass();
				$moduleInstance->handleRequest();

				return;
			}
		}
	}
