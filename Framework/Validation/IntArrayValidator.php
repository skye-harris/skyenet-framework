<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 17/03/2019
	 * Time: 10:16 AM
	 */

	namespace Skyenet\Validation;

	class IntArrayValidator implements ValidationCallback {
		private int $min;
		private int $max;
		private ?string $customErrorMessage;

		public function __construct(?int $minLength = null, ?int $maxLength = null, ?string $customErrorMessage = null) {
			$this->min = $minLength;
			$this->max = $maxLength;
			$this->customErrorMessage = $customErrorMessage;
		}

		/**
		 * @param $input
		 * @return int|null
		 * @throws Exception
		 */
		public function Validate($input): ?int {
			return DataValidator::ForValue($input)
								->int($this->min, $this->max, $this->customErrorMessage)
								->value();
		}
	}