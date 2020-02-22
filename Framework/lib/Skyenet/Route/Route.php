<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/04/2019
	 * Time: 6:05 PM
	 */

	namespace Skyenet\Route;

	// todo: add a catch-all option

	class Route {
		public string $controllerClass;
		public string $functionName;
		public ?array $matchVars = null;
		private array $matchParts;

		public function __construct(?String $urlPath, String $controllerClass, String $functionName) {
			$this->matchParts = array_values(array_filter(explode('/', $urlPath)));
			$this->controllerClass = $controllerClass;
			$this->functionName = $functionName;
		}

		public function match(array $requestParts): bool {
			$partCount = count($this->matchParts);

			if (count($requestParts) !== $partCount) {
				return false;
			}

			$this->matchVars = [];
			for ($i = 0; $i < $partCount; $i++) {
				if ($this->matchParts[$i][0] === '$') {
					$var = substr($this->matchParts[$i], 1);
					$this->matchVars[$var] = urldecode($requestParts[$i]);

					continue;
				}

				if ($requestParts[$i] !== $this->matchParts[$i]) {
					return false;
				}
			}

			return true;
		}
	}