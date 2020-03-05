<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 24/03/2019
	 * Time: 9:29 AM
	 */

	namespace Skyenet\EventManager;

	interface EventListener {

		/**
		 * Return an array of event names that this plugin will listen for
		 *
		 * @return array
		 * @example return [\Model\Crescent\Invoice\Invoice::EVENT_INVOICE_SAVED];
		 */
		public function registerEvents(): array;

		/**
		 * @param Event $event
		 * @return void
		 */
		public function onReceiveEvent(Event $event): void;
	}