<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/08/2019
	 * Time: 6:49 pm
	 */

	namespace Skyenet\Traits;

	trait Singleton {
		public static function getInstance(): self {
			if (!self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		protected function __construct() {
		}

		protected function __clone() {
		}

		private static $instance;
	}