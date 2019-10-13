<?php

	namespace Skyenet\Database\MySQL;

	use Mysqli;
	use Skyenet\ManagedData;
	use Skyenet\Skyenet;
	use Skyenet\Traits\Singleton;

	class Connection {
		use Singleton;

		/**
		 * @param string|null $databaseName
		 * @return Connection
		 * @throws ConnectException
		 */
		public static function getInstance(?string $databaseName = null): Connection {
			if (static::$instance === null) {
				static::$instance = new static($databaseName);
			} else if ($databaseName) {
				static::$instance->setDatabase($databaseName);
			}

			return static::$instance;
		}

		/**
		 * SQLConnection constructor.
		 *
		 * @param string|null $databaseName
		 * @throws ConnectException
		 */
		protected function __construct(?string $databaseName) {
			$defaultDatabase = Skyenet::$CONFIG['DATABASE_DEFAULT_NAME'];
			$databaseHost = Skyenet::$CONFIG['DATABASE_HOST'];
			$databaseUser = Skyenet::$CONFIG['DATABASE_USER'];
			$databasePass = Skyenet::$CONFIG['DATABASE_PASSWORD'];

			$this->setDatabase($databaseName ?? $defaultDatabase);

			@$this->mysqli = new mysqli($databaseHost, $databaseUser, $databasePass, $this->dbName);
			if ($this->mysqli->connect_errno) {
				throw new ConnectException("Connection to database failed with error {$this->mysqli->connect_errno}: {$this->mysqli->connect_error}");
			}
		}

		// End Singleton stuff

		private $dbName = '';
		/**
		 * @var mysqli $mysqli
		 */
		private $mysqli;

		/**
		 * @param string $dbName
		 */
		public function setDatabase(string $dbName): void {
			$this->dbName = $dbName;

			if ($this->mysqli !== null) {
				$this->mysqli->select_db($dbName);
			}
		}

		public function __destruct() {
			if ($this->mysqli !== null && $this->mysqli->ping()) {
				$this->mysqli->close();
			}
		}

		/**
		 * @param string $query
		 * @param mixed  ...$params
		 * @return ResultRow
		 * @throws QueryException
		 */
		public function readSingleRowManaged(string $query, ...$params): ?ResultRow {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			$stmt = $this->prepareStatement($query);
			if ($params) {
				$stmt->bindParams($params);
			}
			$result = $stmt->execute();

			$output = $result->fetch_assoc_managed();
			$result->close();

			return $output;
		}

		/**
		 * @param string $query
		 * @param mixed  ...$params
		 * @return mixed
		 * @throws QueryException
		 */
		public function readSingleRow(string $query, ...$params): ?array {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			$stmt = $this->prepareStatement($query);
			if ($params) {
				$stmt->bindParams($params);
			}
			$result = $stmt->execute();

			$output = $result->fetch_assoc_managed();
			$result->close();

			return $output;
		}

		/**
		 * @param string $query
		 * @param array  $params
		 * @return mixed
		 * @throws QueryException
		 */
		public function readSingleResult(string $query, ...$params) {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			$stmt = $this->prepareStatement($query);
			if ($params) {
				$stmt->bindParams($params);
			}
			$result = $stmt->execute();

			$output = ($row = $result->fetch_row()) ? $row[0] : null;
			$result->close();

			return $output;
		}

		/**
		 * @param string $query
		 * @param mixed  ...$params
		 * @return ManagedData|null
		 * @throws QueryException
		 */
		public function readSingleResultManaged(string $query, ...$params): ?ManagedData {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			$stmt = $this->prepareStatement($query);
			if ($params) {
				$stmt->bindParams($params);
			}
			$result = $stmt->execute();

			$output = ($row = $result->fetch_row()) ? new ManagedData($row[0]) : null;
			$result->close();

			return $output;
		}

		/**
		 * @param       $query
		 * @param array $params
		 * @return Result
		 * @throws QueryException
		 */
		public function query($query, ...$params): Result {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			$stmt = $this->prepareStatement($query);
			if (count($params)) {
				$stmt->bindParams($params);
			}

			return $stmt->execute();
		}


		/**
		 * @param $query
		 * @return Statement
		 * @throws QueryException
		 */
		public function prepareStatement($query): Statement {
			if (!$this->ping()) {
				throw new QueryException('MySQL connection is not currently open');
			}

			return new Statement($this->mysqli, $query);
		}

		public function ping(): bool {
			return $this->mysqli ? $this->mysqli->ping() : false;
		}

		/**
		 * @throws TransactionException
		 */
		public function beginTransaction(): void {
			if (!$this->ping()) {
				throw new TransactionException('MySQL connection is not currently open');
			}

			if (!$this->mysqli->begin_transaction()) {
				throw new TransactionException("Failed to begin transaction: {$this->mysqli->error}");
			}
		}

		/**
		 * @throws TransactionException
		 */
		public function commitTransaction(): void {
			if (!$this->ping()) {
				throw new TransactionException('MySQL connection is not currently open');
			}

			if (!$this->mysqli->commit()) {
				throw new TransactionException("Failed to commit transaction: {$this->mysqli->error}");
			}
		}

		public function rollbackTransaction(): bool {
			if (!$this->ping()) {
				return false;
			}
			//throw new TransactionException("MySQL connection is not currently open");

			/*
			if (!$this->mysqli->rollback())
				throw new TransactionException("Failed to rollback transaction: ".$this->mysqli->error);
			*/

			return $this->mysqli->rollback();
		}

		/**
		 * @param string $savePoint
		 * @throws TransactionException
		 */
		public function releaseSavePoint(string $savePoint): void {
			if (!$this->ping()) {
				throw new TransactionException('MySQL connection is not currently open');
			}

			if (!$this->mysqli->release_savepoint($savePoint)) {
				throw new TransactionException("Failed to release savepoint '{$savePoint}': {$this->mysqli->error}");
			}
		}


		/**
		 * @param string $savePoint
		 * @throws TransactionException
		 */
		public function rollbackToSavePoint(string $savePoint): void {
			if (!$this->ping()) {
				throw new TransactionException('MySQL connection is not currently open');
			}

			if (!$this->mysqli->query("ROLLBACK TO {$savePoint};")) {
				throw new TransactionException("Failed to rollback transaction to savepoint '{$savePoint}': {$this->mysqli->error}");
			}
		}

		/**
		 * @param string $savePoint
		 * @throws TransactionException
		 */
		public function createSavePoint(string $savePoint): void {
			if (!$this->ping()) {
				throw new TransactionException('MySQL connection is not currently open');
			}

			if (!$this->mysqli->savepoint($savePoint)) {
				throw new TransactionException("Failed to create savepoint '{$savePoint}': {$this->mysqli->error}");
			}
		}
	}