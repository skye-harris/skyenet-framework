<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/08/2019
	 * Time: 7:17 pm
	 */

	namespace Skyenet\Traits;

	trait Descriptive {
		public function __toString(): string {
			return get_class($this);
		}
	}