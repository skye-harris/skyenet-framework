<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 7:33 PM
	 */

	namespace Skyenet\Validation;

	class ValidationPatterns {
		public const PATTERN_DATE_YMD = 1;
		public const PATTERN_DATE_DMY = 2;
		public const PATTERN_PHONE_AUSTRALIA = 3;
		public const PATTERN_DATETIME_LOCAL = 4;
		public const PATTERN_UUID = 5;
		public const PATTERN_UUID_OR_EMPTY = 6;

		// for skyenet image post to phone
		public const PATTERN_SECURITY_CAMERA_STILL = 99;

		public const PATTERNS = [
			self::PATTERN_DATE_YMD => [
				'PATTERN' => '/^(\d{4})-(\d{1,2})-(\d{1,2})$/',
				'NAME' => 'Date'
			],

			self::PATTERN_DATE_DMY => [
				'PATTERN' => '/^(\d{1,2})-(\d{1,2})-(\d{4})$/',
				'NAME' => 'Date'
			],

			self::PATTERN_PHONE_AUSTRALIA => [
				'PATTERN' => '/^0\d{9}$/',
				'NAME' => 'Phone Number'
			],

			self::PATTERN_SECURITY_CAMERA_STILL => [
				'PATTERN' => '/^\d{1}_20\d{2}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/',
				'NAME' => 'Security Camera Image Timestamp'
			],

			self::PATTERN_DATETIME_LOCAL => [
				'PATTERN' => '/^((\d{4})-(\d{2})-(\d{2}))T((\d{2}):(\d{2}))$/',
				'NAME' => 'Date-Time'
			],

			self::PATTERN_UUID => [
				'PATTERN' => '/^[a-f0-9]{32}$/i',
				'NAME' => 'UUID'
			],

			self::PATTERN_UUID_OR_EMPTY => [
				'PATTERN' => '/^([a-f0-9]{32}){0,1}$/i',
				'NAME' => 'UUID'
			],

		];
	}
