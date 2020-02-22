<?php

	namespace Skyenet\Validation;

	class ArrayDataValidator extends TypeDataValidator {
		public function unique(): ArrayDataValidator {
			if ($this->rawValue !== NULL) {
				$this->rawValue = array_unique($this->rawValue);
			}

			return $this;
		}

		public function value(?array $default = null): ?array {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}