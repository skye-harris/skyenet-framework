<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:31 PM
	 */

	namespace Skyenet\Validation;

	class IntDataValidator extends TypeDataValidator {
		public function value(?int $default = null): ?int {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}
