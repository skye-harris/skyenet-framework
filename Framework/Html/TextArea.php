<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 7/09/2019
	 * Time: 6:21 pm
	 */

	namespace Skyenet\Html;

	class TextArea extends Input {
		protected string $tagName = 'textarea';
		protected $requiresClosingTag = true;

		public function setRows(int $rows): self {
			$this->setAttribute('rows',$rows);

			return $this;
		}
	}