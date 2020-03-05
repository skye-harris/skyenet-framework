<?php

	namespace CommandLine;

	abstract class CliModule {
		public const DESCRIPTION = 'Base CLI Module Class';
		public const MODULE_NAME = 'module-name';

		protected array $longOpts = [];

		protected function shellExecute(string $command): bool {
			echo "Command: {$command}" . PHP_EOL;

			do {
				$res = strtolower(readline('Execute? [yes/no]: '));
			} while (!in_array($res, ['yes', 'no', 'y', 'n'], true));

			if ($res === 'yes' || $res === 'y') {
				echo 'Executing shell command...' . PHP_EOL;
				$response = shell_exec($command);
				echo $response;
				echo 'Done!' . PHP_EOL;

				return true;
			}

			return false;
		}

		public function defaultHandler(): void {
			echo 'Available options:' . PHP_EOL;

			foreach ($this->longOpts AS $option => $infoArray) {
				$command = str_replace(':', '', $option);
				[$value, $text] = $infoArray;

				$paddedCommandValue = str_pad("{$command}={$value}", 50, ' ', STR_PAD_RIGHT);
				echo "\t--{$paddedCommandValue}{$text}" . PHP_EOL;
			}
		}

		public function handleRequest(): void {
			$options = getopt('', array_keys($this->longOpts));
			if (!count($options)) {
				$this->defaultHandler();

				return;
			}

			foreach ($options AS $key => $value) {
				$key = str_replace('-', '_', $key);

				$this->$key($value);
			}
		}
	}

