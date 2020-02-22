<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 24/03/2019
	 * Time: 9:09 AM
	 */

	namespace Skyenet\EventManager;

	use Error;
	use Skyenet\Traits\Descriptive;

	class EventManager {
		private static array $eventListeners = [];

		/**
		 * @param string $eventListenerClassName
		 * @throws Exception
		 */
		public static function RegisterEventListener(string $eventListenerClassName): void {
			try {
				/** @var EventListener $listener */
				$listener = new $eventListenerClassName();
				$events = $listener->registerEvents();
			} catch (Error $error) {
				throw new Exception("EventListener '{$eventListenerClassName}' could not be instantiated: {$error->getMessage()}");
			}

			self::$eventListeners[] = [$listener, $events];
		}

		public static function BroadcastEvent(Event $event): bool {
			foreach (self::$eventListeners AS $listenerArray) {
				[$listener, $events] = $listenerArray;

				if (!in_array($event->name, $events, true))
					continue;

				/* @var $listener EventListener */
				$listener->onReceiveEvent($event);

				if ($event->stopPropagation) {
					break;
				}
			}

			return $event->isCancelled();
		}
	}