<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 24/03/2019
	 * Time: 3:34 PM
	 */

	namespace Skyenet\EventManager;

	class Event {
		/* @var $name String */
		public string $name;

		/* @var $source Object */
		public $source;

		/* @var $isCancellable bool */
		protected bool $isCancellable;

		/* @var $cancelEvent bool */
		protected bool $cancelEvent = false;

		protected ?string $cancelUserFriendlyMessage = null;

		/* @var $stopPropagation bool */
		public bool $stopPropagation = false;

		public $data;

		/**
		 * Event constructor.
		 *
		 * @param String $eventName
		 * @param mixed  $eventSource The object broadcasting the event
		 * @param mixed  $eventData Event data
		 * @param bool   $isCancellable Is this event notifying of something about to happen that we can cancel?
		 */
		public function __construct(String $eventName, &$eventSource, $eventData = null, bool $isCancellable = false) {
			$this->name = $eventName;
			$this->source = $eventSource;
			$this->data = $eventData;
			$this->isCancellable = $isCancellable;
		}

		public function cancel(string $userFriendlyMessage = null): void {
			$this->cancelEvent = true;
			$this->cancelUserFriendlyMessage = $userFriendlyMessage;
		}

		public function getCancellationUserFriendlyMessage(): ?string {
			return $this->cancelUserFriendlyMessage;
		}

		public function isCancelled(): bool {
			return $this->cancelEvent;
		}

		/**
		 * @return bool
		 */
		public function isCancellable(): bool {
			return $this->isCancellable;
		}
	}