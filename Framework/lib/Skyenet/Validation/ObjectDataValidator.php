<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:33 PM
	 */

	namespace Skyenet\Validation;

	class ObjectDataValidator extends TypeDataValidator {
		public function value($default = null) {
			return ($default !== NULL && $this->rawValue === NULL) ? $default : $this->rawValue;
		}
	}
