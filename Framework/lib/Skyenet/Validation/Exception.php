<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/04/2019
	 * Time: 6:01 PM
	 */

	namespace Skyenet\Validation;

	use Throwable;

	class Exception extends \Skyenet\Exception {
		public function __construct(?string $message = null, ?string $userFriendlyMessage = null, int $code = 0, Throwable $previous = null) {
			parent::__construct($message ?? $userFriendlyMessage ?? 'An unknown validation error occurred', $userFriendlyMessage, $code, $previous);
		}
	}
