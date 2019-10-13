<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:32 PM
	 */

	namespace Skyenet\Validation;

	class BoolDataValidator extends TypeDataValidator {
		public function value(?bool $default = null): ?bool {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}