<?php

	namespace Skyenet\Html;

	use Skyenet\ManagedData;
	use Skyenet\Security\Security;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 7/09/2019
	 * Time: 5:36 pm
	 */
	class HtmlTag {
		protected string $tagName = 'html';
		protected $requiresClosingTag = true;
		protected $attributes = [];
		protected $classes = [];
		protected $children = [];

		protected $innerHTML;

		public function __construct() {
		}

		public function appendChild(HtmlTag $htmlTag): self {
			$this->children[] = $htmlTag;

			return $this;
		}

		/**
		 * @param ManagedData|string $html
		 * @return $this
		 */
		public function setInnerHTML(string $html): self {
			$this->innerHTML = $html instanceof ManagedData ? $html->rawValue() : (string)$html;

			return $this;
		}

		/**
		 * @param ManagedData|string $text
		 * @return $this
		 */
		public function setInnerText(string $text): self {
			$this->innerHTML = $text instanceof ManagedData ? $text->htmlSafe() : Security::HTMLEntities($text);

			return $this;
		}


		/**
		 * @param string             $key
		 * @param ManagedData|string $value
		 * @return $this
		 */
		public function setAttribute(string $key, $value): self {
			$this->attributes[strtolower($key)] = $value instanceof ManagedData ? $value->rawValue() : (string)$value;

			return $this;
		}

		public function setAttributes(array $keyValueArray): self {
			foreach ($keyValueArray AS $key => $value) {
				$this->attributes[strtolower($key)] = $value instanceof ManagedData ? $value->rawValue() : (string)$value;
			}

			return $this;
		}

		public function addClass(string $className): self {
			$this->classes[] = $className;

			return $this;
		}

		public function build(): string {
			$tagOut = [$this->tagName];

			$classes = array_unique($this->classes);
			if (count($classes)) {
				$this->setAttribute('class', implode(' ', $classes));
			}

			if (empty($this->attributes['name']) && $name = $this->attributes['id'] ?? null) {
				$this->setAttribute('name', $name);
			}

			foreach ($this->attributes AS $key => $val) {
				$val = Security::HTMLEntities($val);

				$tagOut[] = "{$key}=\"{$val}\"";
			}

			if (!$this->requiresClosingTag) {
				$tagOut[] = '/';
			} else {
				$tagOut[] = '>';

				foreach ($this->children AS $child) {
					$tagOut[] = $child->build();
				}

				if ($this->innerHTML) {
					$tagOut[] = $this->innerHTML;
				}

				$tagOut[] = "</{$this->tagName}";
			}

			return '<' . implode(' ', $tagOut) . '>';
		}

		public function __toString(): string {
			return $this->build();
		}

		public function getAttribute(string $key): ?string {
			return $this->attributes[strtolower($key)] ?? null;
		}
	}