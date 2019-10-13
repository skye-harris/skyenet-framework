<?php

	namespace Skyenet\Validation;

	use Skyenet\Skyenet;

	class DataValidator {
		protected $rawValue;
		protected $varName = 'Provided data';

		public function value($default = null) {
			return $this->rawValue ?? $default;
		}

		/**
		 * @param string|null $customErrorMessage
		 * @return ArrayDataValidator
		 * @throws Exception
		 */
		public function jsonArray(?string $customErrorMessage = null): ArrayDataValidator {
			if ($this->rawValue !== null) {
				$json = json_decode($this->rawValue, false);

				if ($json === null || !is_array($json)) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a valid JSON array");
				}

				$this->rawValue = $json;
			}

			return new ArrayDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param string|null $customErrorMessage
		 * @return ObjectDataValidator
		 * @throws Exception
		 */
		public function jsonObject(?string $customErrorMessage = null): ObjectDataValidator {
			if ($this->rawValue !== null) {
				$json = json_decode($this->rawValue, false);
				if ($json === null || !is_object($json)) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a valid JSON object");
				}

				$this->rawValue = $json;
			}

			return new ObjectDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param ValidationCallback|null $elementValidationCallback
		 * @param string|null             $customErrorMessage
		 * @return ArrayDataValidator
		 * @throws Exception
		 */
		public function array(?ValidationCallback $elementValidationCallback = null, ?string $customErrorMessage = null): ArrayDataValidator {
			if ($this->rawValue !== null) {
				if (!is_array($this->rawValue)) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be an array");
				}

				if ($elementValidationCallback !== null) {
					foreach ($this->rawValue AS $key => $value) {
						$this->rawValue[$key] = $elementValidationCallback->Validate($value);
					}
				}
			}

			return new ArrayDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param int         $minLength
		 * @param int|null    $maxLength
		 * @param bool        $trim
		 * @param string|null $customErrorMessage
		 * @return StringDataValidator
		 * @throws Exception
		 */
		public function string(int $minLength = 0, ?int $maxLength = null, bool $trim = true, ?string $customErrorMessage = null): StringDataValidator {
			if ($this->rawValue !== null) {
				if (!is_string($this->rawValue)) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a string");
				}

				if ($trim) {
					$this->rawValue = trim($this->rawValue);
				}

				$len = strlen($this->rawValue);

				if ($minLength > $len) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be at least {$minLength} characters in length");
				}

				if ($maxLength !== null && $len > $maxLength) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be no more than {$maxLength} characters in length");
				}
			}

			return new StringDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param bool        $treatEmptyAsNull
		 * @param string|null $customErrorMessage
		 * @return DateDataValidator
		 * @throws Exception
		 */
		public function date(bool $treatEmptyAsNull = true, ?string $customErrorMessage = null): DateDataValidator {
			if ($treatEmptyAsNull && $this->rawValue === '') {
				$this->rawValue = null;
			}

			if ($this->rawValue !== null) {
				if (preg_match(ValidationPatterns::PATTERNS[ValidationPatterns::PATTERN_DATE_YMD]['PATTERN'], $this->rawValue, $matches)) {
					return new DateDataValidator($matches[3], $matches[2], $matches[1], $this->varName);
				}

				/*
				 * DMY is just... ew
				if (preg_match(ValidationPatterns::PATTERNS[ValidationPatterns::PATTERN_DATE_DMY]['PATTERN'],$this->rawValue,$matches))
					return new DateDataValidator($matches[1],$matches[2],$matches[3],$this->varName);
				*/

				throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a Date (YYYY-MM-DD)");
			}

			return new DateDataValidator(null, null, null, $this->varName);
		}

		/**
		 * @param int|null    $min
		 * @param int|null    $max
		 * @param string|null $customErrorMessage
		 * @return IntDataValidator
		 * @throws Exception
		 */
		public function int(?int $min = null, ?int $max = null, ?string $customErrorMessage = null): IntDataValidator {
			if ($this->rawValue !== null) {
				if (filter_var($this->rawValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_OCTAL) === null) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a numeric integer");
				}

				if ($min !== null && $min > $this->rawValue) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a minimum of '{$min}'");
				}

				if ($max !== null && $max < $this->rawValue) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a maximum of '{$max}'");
				}
			}

			$this->rawValue = $this->rawValue === null ? null : (int)$this->rawValue;

			return new IntDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param float|null  $min
		 * @param float|null  $max
		 * @param string|null $customErrorMessage
		 * @return FloatDataValidator
		 * @throws Exception
		 */
		public function float(?float $min = null, ?float $max = null, ?string $customErrorMessage = null): FloatDataValidator {
			if ($this->rawValue !== null) {
				if (filter_var($this->rawValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) === null) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be numeric");
				}

				if ($min !== null && $min > $this->rawValue) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a minimum of '{$min}'");
				}

				if ($max !== null && $max < $this->rawValue) {
					throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a maximum of '{$max}'");
				}
			}

			$this->rawValue = $this->rawValue === null ? null : (float)$this->rawValue;

			return new FloatDataValidator($this->rawValue, $this->varName);
		}

		/**
		 * @param string|null $customErrorMessage
		 * @return BoolDataValidator
		 * @throws Exception
		 */
		public function bool(?string $customErrorMessage = null): BoolDataValidator {
			if ($this->rawValue !== null && filter_var($this->rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
				throw new Exception(null, $customErrorMessage ?? "{$this->varName} must be a boolean");
			}

			$this->rawValue = $this->rawValue === null ? null : (bool)$this->rawValue;

			return new BoolDataValidator($this->rawValue, $this->varName);
		}


		/**
		 * @param String      $indexName
		 * @param bool        $nullable
		 * @param String|null $friendlyDesc
		 * @return DataValidator
		 * @throws Exception
		 */
		public static function GET(String $indexName, bool $nullable = false, ?String $friendlyDesc = null): self {
			$varName = $friendlyDesc ?? $indexName;

			if (!$nullable && !isset($_GET[$indexName])) {
				throw new Exception(null, "Value for {$varName} was not found");
			}

			$value = $_GET[$indexName] ?? null;
			if ($value === null && !$nullable) {
				throw new Exception(null, "{$varName} cannot be null");
			}

			$dataValidation = new self($value);
			$dataValidation->varName = $varName;

			return $dataValidation;
		}

		/**
		 * @param String      $indexName
		 * @param bool        $nullable
		 * @param String|null $friendlyDesc
		 * @return DataValidator
		 * @throws Exception
		 */
		public static function POST(String $indexName, bool $nullable = false, ?String $friendlyDesc = null): self {
			$varName = $friendlyDesc ?? $indexName;

			if (!$nullable && !isset($_POST[$indexName])) {
				throw new Exception(null, "Value for {$varName} was not found");
			}

			$value = $_POST[$indexName] ?? null;
			if ($value === null && !$nullable) {
				throw new Exception(null, "{$varName} cannot be null");
			}

			$dataValidation = new self($value);
			$dataValidation->varName = $varName;

			return $dataValidation;
		}

		/**
		 * @param String      $indexName
		 * @param bool        $nullable
		 * @param String|null $friendlyDesc
		 * @return DataValidator
		 * @throws Exception
		 */
		public static function REQUEST(String $indexName, bool $nullable = false, ?String $friendlyDesc = null): self {
			$varName = $friendlyDesc ?? $indexName;

			if (!$nullable && !isset($_REQUEST[$indexName])) {
				throw new Exception(null, "Value for {$varName} was not found");
			}

			$value = $_REQUEST[$indexName] ?? null;
			if ($value === null && !$nullable) {
				throw new Exception(null, "{$varName} cannot be null");
			}

			$dataValidation = new self($value);
			$dataValidation->varName = $varName;

			return $dataValidation;
		}

		/**
		 * @param String      $variableName
		 * @param String|null $friendlyDesc
		 * @return DataValidator
		 * @throws Exception
		 */
		public static function URLVAR(String $variableName, ?String $friendlyDesc = null): self {
			$varName = $friendlyDesc ?? $variableName;
			$skyeNet = Skyenet::getInstance();

			if (!isset($skyeNet->requestVars[$variableName])) {
				throw new Exception(null, "Value for {$varName} was not found");
			}

			$value = $skyeNet->requestVars[$variableName];

			$dataValidation = new self($value);
			$dataValidation->varName = $varName;

			return $dataValidation;
		}

		/**
		 * @param             $value
		 * @param bool        $nullable
		 * @param String|null $friendlyDesc
		 * @return DataValidator
		 * @throws Exception
		 */
		public static function ForValue($value, bool $nullable = false, ?String $friendlyDesc = null): self {
			$dataValidation = new self($value);
			if ($friendlyDesc !== null) {
				$dataValidation->varName = $friendlyDesc;
			}

			if ($value === null && !$nullable) {
				throw new Exception(null, "{$dataValidation->varName} cannot be null");
			}

			return $dataValidation;
		}

		private function __construct($rawValue) {
			$this->rawValue = $rawValue;
		}
	}