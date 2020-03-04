<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 14/06/2018
	 * Time: 8:14 PM
	 */

	namespace Skyenet;


	use JsonSerializable;
	use Skyenet\Http\UrlLoadable;
	use Skyenet\Security\Security;

	class ManagedData implements JsonSerializable, UrlLoadable {
		private $data;

		public function __construct($data) {
			$this->data = $data;
		}

		public function equals($comparator): bool {
			return $this->data === ($comparator instanceof self ? $comparator() : $comparator);
		}

		public function lessThan($comparator): bool {
			return $this->data < ($comparator instanceof self ? $comparator() : $comparator);
		}

		public function lessThanOrEqual($comparator): bool {
			return $this->data >= ($comparator instanceof self ? $comparator() : $comparator);
		}

		public function greaterThan($comparator): bool {
			return $this->data > ($comparator instanceof self ? $comparator() : $comparator);
		}

		public function greaterThanOrEqual($comparator): bool {
			return $this->data <= ($comparator instanceof self ? $comparator() : $comparator);
		}

		/**
		 * @return string Sanitises the underlying value for HTML
		 */
		public function __toString(): string {
			return $this->htmlSafe();
		}

		/**
		 * @return mixed Returns the raw underlying value
		 */
		public function __invoke() {
			return $this->data;
		}

		public function toJSON(): string {
			return json_encode($this->data, JSON_THROW_ON_ERROR, 512);
		}

		public function htmlSafe(): string {
			return Security::HTMLEntities($this->data);
		}

		public function strlen(): int {
			return strlen($this->data);
		}

		public function bin2hex(): ManagedData {
			return new static(bin2hex($this->data));
		}

		public function hex2bin(): ManagedData {
			return new static(hex2bin($this->data));
		}

		public function substr(int $index, ?int $length): ManagedData {
			return new static(substr($this->data, $index, $length));
		}

		public function str_replace(string $search, string $replace): ManagedData {
			return new static(str_replace($search, $replace, $this->data));
		}

		public function trim(): ManagedData {
			return new static(trim($this->data));
		}

		public function rawValue() {
			return $this->data;
		}

		public function intValue(): int {
			return (int)$this->data;
		}

		public function floatValue(): float {
			return (float)$this->data;
		}

		public function boolValue(): bool {
			return (bool)$this->data;
		}

		public function isBitSet(int $bit): bool {
			return ((int)$this->data & $bit);
		}

		public function __debugInfo(): array {
			return [
				'BOOL' => $this->boolValue() ? 'TRUE' : 'FALSE',
				'HTML' => $this->htmlSafe(),
				'RAW' => $this->data,
			];
		}

		/**
		 * Specify data which should be serialized to JSON
		 *
		 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
		 * @return mixed data which can be serialized by <b>json_encode</b>,
		 * which is a value of any type other than a resource.
		 * @since 5.4.0
		 */
		public function jsonSerialize() {
			return $this->data;
		}

		public static function LoadFromRequestString(string $requestString): UrlLoadable {
			return new static($requestString);
		}
	}