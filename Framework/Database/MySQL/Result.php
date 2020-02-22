<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 3/04/2019
	 * Time: 7:50 PM
	 */

	namespace Skyenet\Database\MySQL;

	use Countable;
	use Iterator;
	use mysqli_result;
	use mysqli_stmt;

	class Result implements Iterator, Countable {
		public int $num_rows = 0;
		public int $affected_rows = 0;
		public int $insert_id = 0;

		private int $_num_rows;
		private int $rowIndex = 0;

		private array $fieldNames = [];

		private ?mysqli_stmt $sqlStatement;
		private array $rowContent = [];

		public function result_metadata(): mysqli_result {
			return $this->sqlStatement->result_metadata();
		}

		/**
		 * Return the current result row
		 *
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 * @since 5.0.0
		 */
		public function current(): ResultRow {
			if (!$this->sqlStatement) {
				return null;
			}

			if (!$this->rowIndex) {
				$this->row();
			}

			return new ResultRow($this->rowContent);
		}

		/**
		 * Move forward to next result row
		 *
		 * @link http://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function next(): void {
			$this->row();
		}

		/**
		 * Return the key of the current result row
		 *
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return mixed scalar on success, or null on failure.
		 * @since 5.0.0
		 */
		public function key() {
			if (!$this->sqlStatement) {
				return null;
			}

			return $this->rowIndex;
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
			if (!$this->sqlStatement || $this->rowIndex >= $this->_num_rows + 1) {
				return false;
			}

			if ($this->_num_rows === 0) {
				return false;
			}

			return true;
		}

		/**
		 * Rewind the Iterator to the first element
		 *
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function rewind(): void {
			$this->data_seek(0);
		}

		/**
		 * @return array
		 */
		public function getFieldNames(): array {
			return $this->fieldNames;
		}

		/**
		 * @param mysqli_stmt $statement
		 * @throws QueryException
		 */
		public function __construct(mysqli_stmt $statement) {
			$this->sqlStatement = $statement;
			$this->sqlStatement->store_result();

			if ($this->sqlStatement->errno) {
				throw new QueryException($this->sqlStatement->error);
			}

			$this->affected_rows = $statement->affected_rows;
			$this->insert_id = $statement->insert_id;
			$this->_num_rows = $this->num_rows = $statement->num_rows;

			if ($this->_num_rows) {
				$meta = $statement->result_metadata();
				if ($metaFields = $meta->fetch_fields()) {
					$fieldNameCounter = [];

					foreach ($metaFields AS $field) {
						// if our field name is already in use in this result set, then lets give each additional a counter
						$fieldNameCount = ($fieldNameCounter[$field->name] ?? null) ? $fieldNameCounter[$field->name] : 0;
						$fieldName = $field->name;

						if ($fieldNameCount) {
							$fieldName .= "_{$fieldNameCount}";
						}
						$fieldNameCounter[$field->name] = $fieldNameCount + 1;

						$this->fieldNames[] = $fieldName;

						$$fieldName = null;
						$this->rowContent[$fieldName] = &$$fieldName;
					}

					call_user_func_array([$this->sqlStatement, 'bind_result'], $this->rowContent);
				}
			}
		}

		private function row(): ?array {
			$this->rowIndex++;

			if (!$this->_num_rows || $this->rowIndex >= ($this->_num_rows + 1)) {
				return null;
			}

			if ($this->sqlStatement && $this->sqlStatement->fetch()) {
				return $this->rowContent;
			}

			return null;
		}

		public function fetch_row(): ?array {
			$row = $this->row();

			return $row === null ? null : array_values($row);
		}

		public function fetch_row_managed(): ?ResultRow {
			$row = $this->row();

			return $row === null ? null : new ResultRow(array_values($row));
		}

		public function fetch_assoc(): ?array {
			return $this->row();
		}

		public function fetch_assoc_managed(): ?ResultRow {
			$row = $this->row();

			if ($row) {
				return new ResultRow($row);
			}

			return null;
		}

		public function data_seek(int $index): void {
			if ($this->sqlStatement) {
				$this->sqlStatement->data_seek($index);
				$this->rowIndex = $index;
			}
		}

		public function close(): void {
			if ($this->sqlStatement) {
				$this->sqlStatement->free_result();
				$this->sqlStatement->close();
			}

			$this->num_rows = $this->_num_rows = 0;
			$this->sqlStatement = null;
		}

		public function __destruct() {
			$this->close();
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
			return $this->_num_rows;
		}
	}