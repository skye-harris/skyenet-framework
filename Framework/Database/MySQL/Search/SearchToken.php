<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 19/02/2019
	 * Time: 5:33 PM
	 */

	namespace Skyenet\Database\MySQL\Search;

	class SearchToken {
		public $isNegated = false;
		public $token;
		public $inQuote = false;
		public $isOpenQuote = false;
		public $isCloseQuote = false;
		public $isExpandable = false;

		public function __construct(?string $token = null, bool $isNegated = false) {
			$this->token = $token;
			$this->isNegated = $isNegated;
			$this->inQuote = false;
		}
	}