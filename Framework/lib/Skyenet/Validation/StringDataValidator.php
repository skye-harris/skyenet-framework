<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:31 PM
	 */

	namespace Skyenet\Validation;

	class StringDataValidator extends TypeDataValidator {
		/**
		 * @param bool        $allowEmpty
		 * @param string|null $customErrorMessage
		 * @return StringDataValidator
		 * @throws Exception
		 */
		public function emailAddress(bool $allowEmpty = false, ?string $customErrorMessage = null): self {
			if ($this->rawValue !== null && !$allowEmpty && filter_var($this->rawValue, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) === null) {
				throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a valid email address");
			}

			return $this;
		}


		/**
		 * @param int|string  $validationPattern
		 * @param string|null $customErrorMessage
		 * @param null        $patternMatches
		 * @return StringDataValidator
		 * @throws Exception
		 */
		public function matchesPattern($validationPattern, ?string $customErrorMessage = null, &$patternMatches = null): self {
			if ($this->rawValue !== null) {
				if (is_int($validationPattern)) {
					if ($patternEntry = ValidationPatterns::PATTERNS[$validationPattern] ?? null) {
						$patternString = $patternEntry['PATTERN'];

						if (!preg_match($patternString, $this->rawValue, $patternMatches)) {
							$patternName = $patternEntry['NAME'];

							throw new Exception(null, $customErrorMessage ?? "{$this->varName} is not an accepted {$patternName} format");
						}
					} else {
						throw new Exception('Pattern Validation failed as the provided Pattern ID does not exist', $customErrorMessage);
					}
				} else if (is_string($validationPattern)) {
					if (!preg_match($validationPattern, $this->rawValue, $patternMatches)) {
						throw new Exception(null, $customErrorMessage ?? "{$this->varName} failed validation");
					}
				} else {
					throw new Exception('Invalid Validation Pattern provided', $customErrorMessage);
				}
			}

			return $this;
		}

		public function value(?string $default = null): ?String {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}
