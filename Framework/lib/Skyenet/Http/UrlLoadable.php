<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 13/10/2019
	 * Time: 2:55 pm
	 */

	namespace Skyenet\Http;

	interface UrlLoadable {
		public static function LoadFromRequestString(string $requestString): self;
	}