<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 7/09/2019
	 * Time: 5:42 pm
	 */

	namespace Skyenet\Html;

	class Input extends HtmlTag {
		protected string $tagName = 'input';
		protected $requiresClosingTag = false;

		/**
		 * @param int $minLength
		 * @return $this
		 */
		public function minLength(int $minLength): self {
			$this->setAttribute('minlength', $minLength);

			return $this;
		}

		/**
		 * @param int $maxLength
		 * @return $this
		 */
		public function maxLength(int $maxLength): self {
			$this->setAttribute('maxlength', $maxLength);

			return $this;
		}

	}