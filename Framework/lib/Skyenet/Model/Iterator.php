<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 14/06/2018
	 * Time: 7:33 PM
	 */

	namespace Skyenet\Model;

	use Countable;
	use Model\Crescent\User\User;
	use SeekableIterator;
	use Skyenet\Cache\ModelCache;
	use Skyenet\Database\MySQL\QueryException;
	use Skyenet\Database\MySQL\Result;
	use Skyenet\Database\MySQL\Statement;
	use Skyenet\Logging\Logger;

	class Iterator implements SeekableIterator, Countable {
		protected string $parentClass;
		protected ?Statement $sqlStatement = null;

		protected ?Result $sqlResult = null;
		protected ?array $currentRow = null;
		protected ?int $currentIndex = null;
		protected bool $ignoreCache = false;

		protected float $currentSearchRelevancyScore = 0;

		public function __construct(Statement $statement, string $modelClass) {
			$this->sqlStatement = $statement;
			$this->parentClass = $modelClass;

			if ($modelClass === ModelData::class) {
				// special case
				$this->ignoreCache = true;
			}

			try {
				$this->sqlResult = $this->sqlStatement->execute();

				$this->currentRow = null;
				$this->currentIndex = null;
			} catch (QueryException $e) {
				$className = static::class;
				Logger::Error("{$className} has failed to load the query results: {$e->getMessage()}");
			}
		}

		public function getCurrentSearchRelevancyScore(): float {
			return $this->currentSearchRelevancyScore;
		}

		public function count(): int {
			return $this->sqlResult ? $this->sqlResult->num_rows : 0;
		}

		public function size(): int {
			return $this->sqlResult ? $this->sqlResult->num_rows : 0;
		}

		/**
		 * Move forward to next element
		 *
		 * @link https://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function next(): void {
			if ($this->sqlResult) {
				$this->currentIndex++;
				$this->fetchRow();
			}
		}

		/**
		 * Return the key of the current element
		 *
		 * @link https://php.net/manual/en/iterator.key.php
		 * @return mixed scalar on success, or null on failure.
		 * @since 5.0.0
		 */
		public function key() {
			return $this->currentIndex;
		}

		/**
		 * Checks if current position is valid
		 *
		 * @link https://php.net/manual/en/iterator.valid.php
		 * @return boolean The return value will be casted to boolean and then evaluated.
		 * Returns true on success or false on failure.
		 * @since 5.0.0
		 */
		public function valid(): bool {
			return $this->currentRow !== null;
		}

		/**
		 * Rewind the Iterator to the first element
		 *
		 * @link https://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 * @since 5.0.0
		 */
		public function rewind(): void {
			if (!$this->sqlResult)
				return;

			if ($this->currentIndex !== 0) {
				$this->seek(0);
			}
		}

		public function __destruct() {
			if ($this->sqlResult) {
				$this->sqlResult->close();
			}
		}

		/**
		 * Return the current element
		 *
		 * @link https://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 * @since 5.0.0
		 */
		public function current() {
			$result = null;

			try {
				if ($this->currentRow) {
					$binaryUuid = $this->currentRow['uuid'];
					if (!$this->ignoreCache && $result = ModelCache::Get($binaryUuid)) {
						return $result;
					}

					/** @var Model $result */
					$result = new $this->parentClass($this->currentRow);
				}
			} catch (LoadException $e) {
				// the iterator is to return null upon failure (no more results), not throw an exception
			}

			return $result;
		}

		/**
		 * Seeks to a position
		 *
		 * @link https://php.net/manual/en/seekableiterator.seek.php
		 * @param int $position <p>
		 * The position to seek to.
		 * </p>
		 * @return void
		 * @since 5.1.0
		 */
		public function seek($position): void {
			if ($this->sqlResult) {
				$this->currentIndex = $position;
				$this->sqlResult->data_seek($position);

				$this->fetchRow();
			}
		}

		protected function fetchRow():void {
			$this->currentRow = $this->sqlResult->fetch_assoc();
			$this->currentSearchRelevancyScore = $this->currentRow ? (float)($this->currentRow['searchRelevanceScore'] ?? 0) : 0;
		}
	}