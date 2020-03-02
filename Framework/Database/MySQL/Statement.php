<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 3/04/2019
	 * Time: 7:49 PM
	 */

	namespace Skyenet\Database\MySQL;

	use mysqli;
	use mysqli_stmt;
	use Skyenet\ManagedData;

	class Statement {
		private ?mysqli_stmt $statement = null;
		private string $query;
		private ?array $bindings = null;

		/**
		 * @param Mysqli $sqlConnection
		 * @param string $query
		 * @throws QueryException
		 */
		public function __construct(mysqli $sqlConnection, string $query) {
			$this->statement = $sqlConnection->prepare($query);
			$this->query = $query;

			if ($this->statement === FALSE) {
				throw new QueryException("SQL Statement failed to compile: '{$query}', {$sqlConnection->error}");
			}
		}

		private function bindArray(string $types, array $params): Statement {
			$this->bindings = $params;

			$refParams = [];
			$refParams[] = &$types;

			foreach ($params as $index => $value) {
				$refParams[] = &$params[$index];
			}

			call_user_func_array([$this->statement, 'bind_param'], $refParams);

			return $this;
		}

		/**
		 * @param mixed ...$params
		 * @return Statement
		 */
		public function bindParams(...$params): Statement {
			$typeString = '';

			if (is_array($params[0]) && count($params) === 1) {
				$params = $params[0];
			}

			// reindex our array to ensure it is numerically indexed
			$params = array_values($params);

			$len = count($params);
			/** @noinspection ForeachInvariantsInspection */
			for ($i = 0; $i < $len; $i++) {
				$p = $params[$i];

				if (is_string($p)) {
					$typeString .= 's';
				}
				else if (is_int($p)) {
					$typeString .= 'i';
				}
				else if (is_float($p)) {
					$typeString .= 'd';
				}
				else if ($p instanceof ManagedData) {
					$params[$i--] = $p->rawValue();
				}
				else {
					$typeString .= 'b';
				}
			}

			$this->bindArray($typeString, $params);

			return $this;
		}

		/**
		 * Execute the prepared statement, and return a Result object
		 *
		 * @return Result Returns an SQLResult object
		 * @throws QueryException
		 */
		public function execute(): Result {
			$this->statement->execute();

			return new Result($this->statement);
		}
	}