<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 28/09/2019
	 * Time: 4:56 pm
	 */
	namespace Skyenet\Logging;

	class Logger {
		public static function Error(string $errorText):void {
			$backTrace = debug_backtrace();

			$accessMethod = $backTrace[0]['type'];
			$lineNumber = $backTrace[0]['line'];
			$functionName = $backTrace[1]['function'];
			$className = $backTrace[1]['class'];

			$text = "{$className}{$accessMethod}{$functionName}() [{$lineNumber}]: $errorText";

			error_log($text);
		}
	}