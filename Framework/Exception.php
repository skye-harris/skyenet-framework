<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/01/2019
	 * Time: 6:02 PM
	 */

	namespace Skyenet;

	use Throwable;

	class Exception extends \Exception {
		private $userFriendlyMessage = 'An unknown error occurred';

		public function getUserFriendlyMessage(): ?String {
			return $this->userFriendlyMessage;
		}

		public function __construct(?string $message = 'An unknown error occurred', ?string $userFriendlyMessage = null, int $code = 0, Throwable $previous = null) {
			parent::__construct($message, $code, $previous);

			if ($userFriendlyMessage) {
				$this->userFriendlyMessage = $userFriendlyMessage;
			} else if ($previous instanceof self) {
				$this->userFriendlyMessage = $previous->getUserFriendlyMessage();
			}
		}
	}