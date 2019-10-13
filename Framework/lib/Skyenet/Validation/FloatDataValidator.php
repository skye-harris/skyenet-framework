<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:31 PM
	 */

	namespace Skyenet\Validation;

	class FloatDataValidator extends TypeDataValidator {
		public function value(?float $default = null): ?float {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}
