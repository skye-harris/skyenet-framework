<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 3/04/2019
	 * Time: 7:51 PM
	 */

	namespace Skyenet\Database\MySQL;

	use ArrayAccess;
	use Countable;
	use Iterator;
	use Skyenet\ManagedData;

	class ResultRow implements ArrayAccess, Iterator, Countable {
		private array $data = [];
		private array $assocIndex = [];
		private int $foreachIndex = 0;

		public function __construct(array $assocRowData) {
			foreach ($assocRowData AS $key => $val) {
				$this->data[$key] = new ManagedData($val);
				$this->assocIndex[] = $key;
			}
		}

		/**
		 * Whether a offset exists
		 *
		 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
		 * @param mixed $offset <p>
		 * An offset to check for.
		 * </p>
		 * @return boolean true on success or false on failure.
		 * </p>
		 * <p>
		 * The return value will be casted to boolean if non-boolean was returned.
		 * @since 5.0.0
		 */
		public function offsetExists($offset): bool {
			return isset($this->data[$offset]);
		}

		/**
		 * Offset to retrieve
		 *
		 * @link http://php.net/manual/en/arrayaccess.offsetget.php
		 * @param mixed $offset <p>
		 * The offset to retrieve.
		 * </p>
		 * @return mixed Can return all value types.
		 * @since 5.0.0
		 */
		public function offsetGet($offset): ManagedData {
			return $this->data[$offset] ?? null;
		}

		/**
		 * Offset to set
		 *
		 * @link http://php.net/manual/en/arrayaccess.offsetset.php
		 * @param mixed $offset <p>
		 * The offset to assign the value to.
		 * </p>
		 * @param mixed $value <p>
		 * The value to set.
		 * </p>
		 * @return void
		 * @since 5.0.0
		 */
		public function offsetSet($offset, $value): void {
			// null, just do nothing
		}

		/**
		 * Offset to unset
		 *
		 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
		 * @param mixed $offset <p>
		 * The offset to unset.
		 * </p>
		 * @return void
		 * @since 5.0.0
		 */
		public function offsetUnset($offset): void {
			unset($this->data[$offset]);
		}

		/**
		 * Return the current element
		 *
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 * @since 5.0.0
		 */
		public function current(): ?ManagedData {
			return $this->data[$this->assocIndex[$this->foreachIndex] ?? null] ?? null;
		}

		/**
		 * Move forward to next element
		 *
		 * @link http://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function next(): void {
			$this->foreachIndex++;
		}

		/**
		 * Return the key of the current element
		 *
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return mixed scalar on success, or null on failure.
		 * @since 5.0.0
		 */
		public function key() {
			return $this->assocIndex[$this->foreachIndex];
		}

		/**
		 * Checks if current position is valid
		 *
		 * @link http://php.net/manual/en/iterator.valid.php
		 * @return boolean The return value will be casted to boolean and then evaluated.
		 * Returns true on success or false on failure.
		 * @since 5.0.0
		 */
		public function valid(): bool {
			return isset($this->data[$this->assocIndex[$this->foreachIndex] ?? null]);
		}

		/**
		 * Rewind the Iterator to the first element
		 *
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function rewind(): void {
			$this->foreachIndex = 0;
		}

		public function __debugInfo() {
			$output = [];

			foreach ($this->data AS $key => $val) {
				/* @var $val ManagedData */
				$output[$key] = $val->rawValue();
			}

			return $output;
		}

		/**
		 * Count elements of an object
		 *
		 * @link http://php.net/manual/en/countable.count.php
		 * @return int The custom count as an integer.
		 * </p>
		 * <p>
		 * The return value is cast to an integer.
		 * @since 5.1.0
		 */
		public function count(): int {
			return count($this->data);
		}

		public function getFieldNames(): array {
			return array_keys($this->data);
		}

		public function toArray(): array {
			return $this->data;
		}
	}