<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/04/2019
	 * Time: 6:02 PM
	 */

	namespace Skyenet\Security;

	use Throwable;

	class Exception extends \Skyenet\Exception {
		public function __construct(string $message = '', ?string $userFriendlyMessage = null, int $code = 0, Throwable $previous = null) {
			parent::__construct($message, $userFriendlyMessage ?? 'Access Denied', $code, $previous);
		}
	}