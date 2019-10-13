<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/04/2019
	 * Time: 6:01 PM
	 */

	namespace Skyenet\Validation;

	interface ValidationCallback {
		public function Validate($input);
	}