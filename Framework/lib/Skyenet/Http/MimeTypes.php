<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 3/02/2019
	 * Time: 2:10 PM
	 */

	namespace Skyenet\Http;


	class MimeTypes {
		public const APPLICATION = 'application/*';
		public const APPLICATION_JSON = 'application/json';

		public const TEXT = 'text/*';
		public const TEXT_CSS = 'text/css';
		public const TEXT_CSV = 'text/csv';
		public const TEXT_HTML = 'text/html';
		public const TEXT_JAVASCRIPT = 'text/javascript';
		public const TEXT_JSON = 'text/json';
		public const TEXT_PLAIN = 'text/plain';

		public const IMAGE_PNG = 'image/png';
		public const IMAGE_JPEG = 'image/jpeg';
		public const IMAGE_BMP = 'image/bmp';

		public const FILE_DEFAULT_MIMETYPES = [
			'JPG' => self::IMAGE_JPEG,
			'JPEG' => self::IMAGE_JPEG,
			'BMP' => self::IMAGE_BMP,
			'PNG' => self::IMAGE_PNG,

			'HTM' => self::TEXT_HTML,
			'HTML' => self::TEXT_HTML,
			'CSS' => self::TEXT_CSS,
			'JS' => self::TEXT_JAVASCRIPT,
			'TXT' => self::TEXT_PLAIN,
			'CSV' => self::TEXT_CSV,
		];
	}