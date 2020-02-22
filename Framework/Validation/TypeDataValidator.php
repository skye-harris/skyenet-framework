<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:29 PM
	 */

	namespace Skyenet\Validation;

	abstract class TypeDataValidator {
		protected $rawValue;
		protected $varName = 'Provided data';

		public function __construct($rawValue, string $varName) {
			$this->rawValue = $rawValue;
			$this->varName = $varName;
		}
	}
