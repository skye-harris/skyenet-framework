<?php

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 1/06/2017
	 * Time: 4:53 PM
	 */

	namespace Skyenet\View;

	// HTML Template file loader class
	use Skyenet\ManagedData;
	use Skyenet\Skyenet;

	class View {
		private string $templateData;

		protected function loadView(string $filePath): bool {
			return file_exists($filePath) && ($this->templateData = file_get_contents($filePath, TRUE)) !== FALSE;
		}

		/**
		 * ViewTemplate constructor.
		 *
		 * @param string $viewFileName
		 * @throws Exception
		 */
		public function __construct(string $viewFileName) {
			$rootPath = Skyenet::getInstance()->rootPath();

			// Test if the path is relative to our rootPath
			$filePath = "{$rootPath}/{$viewFileName}.html";

			if ($this->loadView($filePath)) {
				return;
			}

			// Now test in our expected App/Views path
			$filePath = "{$rootPath}/App/Views/{$viewFileName}.html";

			if ($this->loadView($filePath)) {
				return;
			}

			throw new Exception("Requested template '{$viewFileName}' was not found");
		}

		//todo: fix: does not handle nested conditions properly
		public function buildOutputNew(?array $namedVariableArray = null, bool $conditionChecks = false): string {
			$vars = [];
			if ($namedVariableArray) {
				foreach ($namedVariableArray AS $key => $val) {
					$vars['{$' . $key . '}'] = is_array($val) ? implode($val) : $val;
				}
			}

			$compiledString = $this->templateData;

			// basic conditional branching within templates
			if ($conditionChecks === true) {
				// Test for @if/@endif constructs, supports nested
				$compiledString = preg_replace_callback('/@if\s*\((((?!@if).)*)\)(((?!@if).)*)(?:(?R)|)@endif/sU', static function ($matches) use ($namedVariableArray) {
					$variableName = trim($matches[1]);
					$trueText = $matches[3];

					$operandNOT = false;

					$operand = null;
					$comparator = null;
					$isTrue = false;
					$failOver = false;

					// if our condition begins with !, negate it
					if ($variableName[0] === '!') {
						$operandNOT = true;
						$variableName = trim(substr($variableName, 1));
					}

					// Strip $ from the variable name, if present
					if ($variableName[0] === '$') {
						$variableName = trim(substr($variableName, 1));
					}

					// todo: build some logic to parse condition strings to support more complex operations
					// e.g. allowing both variables or constants on either side of the test
					// perhaps even performing multiple tests, supporting brackets

					if (strpos($variableName, '==') !== false) {
						$parts = explode('==', $variableName);
						$comparator = trim($parts[1]);
						$variableName = trim($parts[0]);

						if ($comparator[0] === '\'' || $comparator[0] === '"') {
							$comparator = substr($comparator, 1, -1);
						} else if ($comparator[0] === '$') {
							$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
						}

						$operand = '==';
					} else if (strpos($variableName, '>') !== false) {
						$parts = explode('>', $variableName);
						$comparator = trim($parts[1]);
						$variableName = trim($parts[0]);
						if ($comparator[0] === '$') {
							$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
						}

						$operand = '>';
					} else if (strpos($variableName, '<') !== false) {
						$parts = explode('<', $variableName);
						$comparator = trim($parts[1]);
						$variableName = trim($parts[0]);
						if ($comparator[0] === '$') {
							$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
						}

						$operand = '<';

					} else if (strpos($variableName, '&') !== false) {
						$parts = explode('&', $variableName);
						$comparator = trim($parts[1]);
						$variableName = trim($parts[0]);

						if ($comparator[0] === '$') {
							if (isset($namedVariableArray[substr($comparator, 1)])) {
								$comparator = (int)($namedVariableArray[substr($comparator, 1)] ?? 0);
							} else {
								$comparator = 0;
								$failOver = true;
							}
						}

						$operand = '&';
					}

					$varVal = $namedVariableArray[$variableName] ?? null;
					if ($varVal instanceof ManagedData) {
						$varVal = $varVal->rawValue();
					}

					if (is_bool($varVal)) {
						$varVal = (int)$varVal;
					}

					/** @noinspection TypeUnsafeComparisonInspection */
					if (($comparator == null && $comparator !== FALSE) && $varVal != null && !$failOver) {
						$isTrue = true;
					} else if ($comparator !== null && $varVal !== null && !$failOver) {
						// perform our condition check
						switch ($operand) {
							case '==':
								/** @noinspection TypeUnsafeComparisonInspection */
								$isTrue = ($varVal == $comparator);
								break;

							case '>':
								$isTrue = ($varVal > $comparator);
								break;

							case '<':
								$isTrue = ($varVal < $comparator);
								break;

							case '&':
								$isTrue = ($varVal & $comparator);
								break;

							default:
								break;
						}
					}

					if (($isTrue && !$operandNOT) || (!$isTrue && $operandNOT)) {
						// If our initial condition is true and we have an @else branch, cull it
						if ($pos = strpos($trueText, '@else')) {
							$trueText = trim(substr($trueText, 0, $pos));
						}
					} else if ($pos = strpos($trueText, '@else')) {
						// Cull the initial branch, take the @else
						$trueText = trim(substr($trueText, $pos + 5));
					} else {
						$trueText = '';
					}

					return $trueText;
				}, $compiledString);
			}

			// Place variables into the final compiled string
			// Doing so last prevents placeholders or conditional tests being XSS'd into and then processed
			$compiledString = strtr($compiledString, $vars);

			return trim($compiledString);
		}

		// Send back the view HTML, with any provided variables completed in the associative array
		public function buildOutput(?array $namedVariableArray = null, bool $conditionChecks = false): string {
			$vars = [];
			if ($namedVariableArray) {
				foreach ($namedVariableArray AS $key => $val) {
					$vars['{$' . $key . '}'] = is_array($val) ? implode($val) : $val;
				}
			}

			$compiledString = $this->templateData;

			// boolean condition checking within templates
			if ($conditionChecks === true) {
				while (preg_match_all('/@if\s*\((((?!@if).)*)\)(((?!@if).)*)(?:(?R)|)@endif/sU', $compiledString, $matches)) {
					$matchCount = count($matches[0]);

					for ($i = 0; $i < $matchCount; $i++) {
						$replaceText = $matches[0][$i];
						$variableName = trim($matches[1][$i]);
						$trueText = $matches[3][$i];

						$operandNOT = false;

						$operand = null;
						$comparator = null;
						$isTrue = false;
						$failOver = false;

						if ($variableName[0] === '!') {
							$operandNOT = true;
							$variableName = trim(substr($variableName, 1));
						}

						if ($variableName[0] === '$') {
							$variableName = trim(substr($variableName, 1));
						}

						if (strpos($variableName, '==') !== false) {
							$parts = explode('==', $variableName);
							$comparator = trim($parts[1]);
							$variableName = trim($parts[0]);

							if ($comparator[0] === '\'' || $comparator[0] === '"') {
								$comparator = substr($comparator, 1, -1);
							} else if ($comparator[0] === '$') {
								$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
							}

							$operand = '==';
						} else if (strpos($variableName, '>') !== false) {
							$parts = explode('>', $variableName);
							$comparator = trim($parts[1]);
							$variableName = trim($parts[0]);
							if ($comparator[0] === '$') {
								$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
							}

							$operand = '>';
						} else if (strpos($variableName, '<') !== false) {
							$parts = explode('<', $variableName);
							$comparator = trim($parts[1]);
							$variableName = trim($parts[0]);
							if ($comparator[0] === '$') {
								$comparator = $namedVariableArray[substr($comparator, 1)] ?? null;
							}

							$operand = '<';

						} else if (strpos($variableName, '&') !== false) {
							$parts = explode('&', $variableName);
							$comparator = trim($parts[1]);
							$variableName = trim($parts[0]);

							if ($comparator[0] === '$') {
								if (isset($namedVariableArray[substr($comparator, 1)])) {
									$comparator = (int)($namedVariableArray[substr($comparator, 1)] ?? 0);
								} else {
									$comparator = 0;
									$failOver = true;
								}
							}

							$operand = '&';
						}

						$varVal = $namedVariableArray[$variableName] ?? null;
						if ($varVal instanceof ManagedData) {
							$varVal = $varVal->rawValue();
						}

						if (is_bool($varVal)) {
							$varVal = (int)$varVal;
						}

						/** @noinspection TypeUnsafeComparisonInspection */
						if (($comparator == null && $comparator !== FALSE) && $varVal != null && !$failOver) {
							$isTrue = true;
						} else if ($comparator !== null && $varVal !== null && !$failOver) {
							switch ($operand) {
								case '==':
									/** @noinspection TypeUnsafeComparisonInspection */
									$isTrue = ($varVal == $comparator);
									break;

								case '>':
									$isTrue = ($varVal > $comparator);
									break;

								case '<':
									$isTrue = ($varVal < $comparator);
									break;

								case '&':
									$isTrue = ($varVal & $comparator);
									break;

								default:
									break;
							}
						}

						if (($isTrue && !$operandNOT) || (!$isTrue && $operandNOT)) {
							// If our initial condition is true and we have an @else branch, cull it
							if ($pos = strpos($trueText, '@else')) {
								$trueText = trim(substr($trueText, 0, $pos));
							}

							$compiledString = str_replace($replaceText, $trueText, $compiledString);

							continue;
						}

						// If our initial condition failed, cull that branch and take our alternate, if one exists
						if ($pos = strpos($trueText, '@else')) {
							$trueText = trim(substr($trueText, $pos + 5));
						} else {
							$trueText = '';
						}

						$compiledString = str_replace($replaceText, $trueText, $compiledString);
					}
				}
			}

			$compiledString = strtr($compiledString, $vars);

			return $compiledString;
		}
	}