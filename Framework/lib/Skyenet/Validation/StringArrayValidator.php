<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 17/03/2019
	 * Time: 10:16 AM
	 */

	namespace Skyenet\Validation;

	class StringArrayValidator implements ValidationCallback {
		private $min;
		private $max;
		private $trim;
		private $customErrorMessage;

		public function __construct(int $minLength = 0, ?int $maxLength = null, bool $trim = true, ?string $customErrorMessage = null) {
			$this->min = $minLength;
			$this->max = $maxLength;
			$this->trim = $trim;
			$this->customErrorMessage = $customErrorMessage;
		}

		/**
		 * @param $input
		 * @return String|null
		 * @throws Exception
		 */
		public function Validate($input): ?string {
			return DataValidator::ForValue($input)
								->string($this->min, $this->max, $this->trim, $this->customErrorMessage)
								->value();
		}
	}