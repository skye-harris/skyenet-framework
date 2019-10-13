<?php

	namespace Skyenet\Model;

	use Skyenet\Cache\ModelCache;
	use Skyenet\Database\MySQL\ConnectException;
	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Database\MySQL\QueryException;
	use Skyenet\Database\MySQL\Search\SearchTokeniser;
	use Skyenet\Database\MySQL\Statement;
	use Skyenet\EventManager\Event;
	use Skyenet\EventManager\EventManager;
	use Skyenet\ManagedData;
	use Skyenet\Security\Security;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 13/05/2017
	 * Time: 4:37 PM
	 */
	abstract class Model implements \JsonSerializable {
		protected array $_variableKeys = [];
		protected array $_variableMap = [];
		protected array $_dirtyVars = [];
		/*
		 * eg:
		 * [
		 * 	'getterFunction' => [Related::class, 'relatedUuid'],
		 * ]
		 *
		 * Where relatedUuid is the related fieldname from this class/table
		 */
		public const _oneToOne = [];

		/*
		 * eg:
		 * [
		 * 	'getterFunction' => [Related::class, 'relatedUuid'],
		 * ]
		 *
		 * Where relatedUuid is the related fieldname from the related class/table
		 */
		public const _oneToMany = [];

		protected ?string $_uuid = null;
		protected bool $_isNew = true;

		public const EVENT_PRE_SAVE = 'MODEL:PRE_SAVE';
		public const EVENT_POST_SAVE = 'MODEL:POST_SAVE';
		public const EVENT_PRE_DELETE = 'MODEL:PRE_DELETE';
		public const EVENT_POST_DELETE = 'MODEL:POST_DELETE';
		public const EVENT_POST_LOAD = 'MODEL:POST_LOAD';

		// PUBLIC FUNCTIONS

		public function __construct(?array $assocArray = null) {
			$this->_uuid = Security::UUID(false);
			$this->_variableKeys = $this->_dirtyVars = array_keys($this->_variableMap);

			if ($assocArray) {
				$this->initialise($assocArray);
			}
		}

		/**
		 * @param $name
		 * @param $arguments
		 * @return Iterator
		 * @throws Exception
		 */
		public function __call($name, $arguments) {
			if ($oneToOne = static::_oneToOne[$name] ?? null) {
				[$relatedClass, $relationKey] = $oneToOne;

				try {
					// should we let this just throw the LoadException here?
					/** @var Model $relatedClass */
					return $relatedClass::LoadByUuid($this->_variableMap[$relationKey]);
				} catch (LoadException $exception) {
					return null;
				}
			}

			if ($oneToMany = static::_oneToMany[$name] ?? null) {
				[$relatedClass, $relationKey] = $oneToMany;

				/** @var Model $relatedClass */
				return $relatedClass::LoadEx("{$relationKey}=UNHEX(?)", [$this->_uuid]);
			}

			throw new Exception("No method to invoke with name '{$name}'");
		}

		public function __get($name): ManagedData {
			return new ManagedData($this->_variableMap[$name] ?? null);
		}

		/**
		 * @param $name
		 * @param $value
		 * @throws SaveException
		 */
		public function __set(string $name, $value): void {
			if ($value instanceof ManagedData) {
				$realVal = $value();
			} else if ($value instanceof self) {
				$realVal = $value->getUuid(true);
			} else {
				$realVal = $value;
			}

			if (!in_array($name, $this->_variableKeys, true)) {
				$myClassName = get_class($this);

				throw new SaveException("Object {$myClassName} has no property named '{$name}'");
			}

			if ($realVal !== $this->_variableMap[$name]) {
				if (!in_array($name, $this->_dirtyVars, true)) {
					$this->_dirtyVars[] = $name;
				}

				$this->_variableMap[$name] = $realVal;
			}
		}

		public function __isset($name): bool {
			return isset($this->_variableMap[$name]);
		}

		private function __clone() {
		}

		public function getUuid(bool $binaryForm = false): string {
			return $binaryForm ? hex2bin($this->_uuid) : $this->_uuid;
		}

		public function toArray(): array {
			return array_merge(['uuid' => $this->_uuid], $this->_variableMap);
		}

		public function isNew(): bool {
			return $this->_isNew;
		}

		public function isDirty(): bool {
			return count($this->_dirtyVars) > 0;
		}

		/**
		 * @throws SaveException
		 */
		public function delete(): void {
			if ($this->_isNew) {
				return;
			}

			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				throw new SaveException("Failed to delete object due to a MySQL\\ConnectException", null, 0, $e);
			}

			try {
				// Broadcast our PRE_DELETE event.. if we are returned false here, then bail-out
				// Should we throw an exception here? or return some kind of failure signal?

				if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_DELETE, $this, null, true))) {
					throw new SaveException('Model save rejected due to a cancelled EVENT_PRE_SAVE event', $event->getCancellationUserFriendlyMessage());
				}

				/** @noinspection SqlResolve */
				$res = $sql->prepareStatement("DELETE FROM {$this::TableName()} WHERE uuid=UNHEX(?) LIMIT 1")
						   ->bindParams($this->_uuid)
						   ->execute();

				$this->_isNew = true;

				// If we have an affected row, then broadcast our POST_DELETE event
				if ($res->affected_rows) {
					EventManager::BroadcastEvent(new Event(static::EVENT_POST_DELETE, $this));
				}

			} catch (QueryException $e) {
				throw new SaveException("Failed to delete object due to a MySQL\\QueryException", null, 0, $e);
			}
		}

		/**
		 * @throws SaveException
		 */
		public function save(): void {
			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				throw new SaveException('Failed to save object due to a MySQL\\ConnectException', null, 0, $e);
			}

			// Broadcast our PRE_SAVE event.. if we are returned false here, then bail-out
			if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_SAVE, $this, null, true))) {
				throw new SaveException('Model save rejected due to a cancelled EVENT_PRE_SAVE event', $event->getCancellationUserFriendlyMessage());
			}

			$values = [];
			$queryPart = [];
			foreach ($this->_dirtyVars AS $key) {
				if ($key === 'uuid') {
					continue;
				}

				$queryPart[] = !$this->_isNew ? "{$key}=?" : $key;

				$values[] = $this->_variableMap[$key];
			}

			if (count($values)) {
				$queryPart = implode(',', $queryPart);


				if (!$this->_isNew) {
					$query = "# noinspection SqlInsertValues
					UPDATE {$this::TableName()} SET {$queryPart} WHERE uuid=UNHEX(?) LIMIT 1";
				} else {
					$placeholderPart = implode(',', array_fill(0, count($values), '?'));

					$query = "# noinspection SqlInsertValues
					INSERT INTO {$this::TableName()} ({$queryPart},uuid) VALUES ({$placeholderPart},UNHEX(?));";
				}
				$values[] = $this->_uuid;

				try {
					$res = $sql->prepareStatement($query)
							   ->bindParams($values)
							   ->execute();

					$res->Close();

					if ($this->_isNew)
						$this->cacheSelf();

					$this->_isNew = false;

					// If we have an affected row, then broadcast our POST_SAVE event
					if ($res->affected_rows) {
						EventManager::BroadcastEvent(new Event($this::EVENT_POST_SAVE, $this));
					}
				} catch (QueryException $e) {
					throw new SaveException("Failed to save object due to a MySQL\\QueryException: {$e->getMessage()}", null, 0, $e);
				}
			}
		}

		public function jsonSerialize() {
			$myData = $this->_variableMap;

			foreach ($myData AS $key => $value) {
				if (stripos($key, 'uuid') && strlen($value) === 16) {
					$myData[$key] = bin2hex($value);
				}
			}

			return $myData;
		}

		// PROTECTED FUNCTIONS

		/**
		 * @param string $binaryUuid
		 * @throws LoadException
		 */
		protected function internalLoad(string $binaryUuid): void {
			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				throw new LoadException("Failed to load object due to a MySQL\\ConnectException", null, 0, $e);
			}

			try {
				/** @noinspection SqlResolve */
				$res = $sql->query("SELECT * FROM {$this::TableName()} WHERE uuid=? LIMIT 1 FOR UPDATE;", $binaryUuid);
			} catch (QueryException $e) {
				throw new LoadException('Failed to load object due to a MySQL\\QueryException', null, 0, $e);
			}

			if ($res->num_rows !== 1) {
				$res->close();

				throw new LoadException('Failed to load object due to no results returned');
			}

			$row = $res->fetch_assoc();
			$res->Close();

			$this->initialise($row);
		}

		/**
		 * @param array $assocArray
		 */
		protected function initialise(array $assocArray): void {
			if ($uuidValue = $assocArray['uuid'] ?? null) {
				unset($assocArray['uuid']);

				$this->_uuid = self::CleanUuid($uuidValue);
				$this->_isNew = false;
			}

			foreach ($assocArray AS $key => $val) {
				$field = $assocArray[$key];

				$this->_variableMap[$key] = $field instanceof ManagedData ? $field->rawValue() : ($field ?? null);
			}

			$this->_dirtyVars = [];

			// todo: check for collision?
			$this->cacheSelf();

			// Broadcast our POST_LOAD event
			EventManager::BroadcastEvent(new Event(static::EVENT_POST_LOAD, $this));
		}

		protected function cacheSelf(): void {
			ModelCache::Cache($this);
		}

		// PUBLIC STATIC FUNCTIONS

		/**
		 * @param string|ManagedData $uuid
		 * @return mixed|Model|null
		 * @throws LoadException
		 */
		public static function LoadByUuid($uuid): self {
			$binUuid = self::BinaryUuid($uuid);

			if ($obj = ModelCache::Get($binUuid)) {
				return $obj;
			}

			$class = static::class;

			/** @var Model $obj */
			$obj = new $class();
			$obj->internalLoad($binUuid);

			return $obj;
		}

		/**
		 * @param string|null $whereString
		 * @param array|null  $whereVariables
		 * @param string|null $orderBy
		 * @param int|null    $limit
		 * @return Iterator
		 * @throws LoadException
		 */
		public static function LoadEx(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null, ?int $limit = null): Iterator {
			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				throw new LoadException("Failed to load object due to a MySQL\\ConnectException", null, 0, $e);
			}

			$wherePart = $whereString ? "WHERE {$whereString}" : null;
			$limitPart = $limit ? "LIMIT {$limit}" : null;
			$orderPart = $orderBy ? "ORDER BY {$orderBy}" : null;

			try {
				$tableName = static::TableName();

				$stmt = $sql->prepareStatement("SELECT * FROM {$tableName} {$wherePart} {$orderPart} {$limitPart} FOR UPDATE;");
				if ($whereVariables && count($whereVariables)) {
					$stmt->bindParams($whereVariables);
				}

				return static::InstantiateClassIterator($stmt);
			} catch (QueryException $e) {
				throw new LoadException("Failed to load object due to a MySQL\\QueryException", null, 0, $e);
			}
		}

		/**
		 * @param string|null $whereString
		 * @param array|null  $whereVariables
		 * @param string|null $orderBy
		 * @return mixed
		 * @throws LoadException
		 */
		public static function LoadOne(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null): self {
			$iterator = static::LoadEx($whereString, $whereVariables, $orderBy, 1);
			$iterator->rewind();

			if (!$iterator->count()) {
				throw new LoadException('No results found');
			}

			return $iterator->current();
		}

		public static function TableName(): string {
			$classParts = explode('\\', static::class);

			return end($classParts);
		}

		public static function BinaryUuid($uuid): string {
			// if of ManagedData type, extract the raw value to work with
			if ($uuid instanceof ManagedData) {
				$uuid = $uuid->rawValue();
			}

			// if we are 16 bytes long, then we are most likely a binary form UUID
			if (strlen($uuid) === 16) {
				return $uuid;
			}

			return hex2bin($uuid);
		}

		// PROTECTED STATIC FUNCTIONS

		/**
		 * @param array           $searchFields
		 * @param SearchTokeniser $searchTokeniser
		 * @param string|null     $whereString
		 * @param array|null      $whereVariables
		 * @param string|null     $orderBy
		 * @param int             $limit
		 * @return Iterator
		 * @throws LoadException
		 */
		protected static function LoadWithSearch(array $searchFields, SearchTokeniser $searchTokeniser, ?string $whereString = null, array $whereVariables = null, ?string $orderBy = 'searchRelevanceScore DESC', ?int $limit = null): Iterator {
			if (!$searchTokeniser->searchTerm) {
				throw new LoadException('Failed to perform Model Search as no valid Search Term available in the SearchTokeniser', 'No valid search term provided');
			}

			$searchFieldPart = implode(',', $searchFields);

			$whereVars = $whereVariables ? array_merge([$searchTokeniser->searchTerm], $whereVariables, [$searchTokeniser->searchTerm]) : [$searchTokeniser->searchTerm, $searchTokeniser->searchTerm];
			$wherePart = $whereString ? "{$whereString} AND" : null;

			$orderByPart = $orderBy ? "ORDER BY {$orderBy}" : null;
			$limitPart = ($limit !== null) ? "LIMIT {$limit}" : null;

			$tableName = static::TableName();

			$searchQuery = "
SELECT *, (MATCH({$searchFieldPart}) AGAINST (? IN BOOLEAN MODE)) AS searchRelevanceScore

FROM {$tableName}

WHERE {$wherePart} MATCH({$searchFieldPart}) AGAINST (? IN BOOLEAN MODE)

{$orderByPart}

{$limitPart}
";

			try {
				$sqlConnection = Connection::getInstance();
				$stmt = $sqlConnection->prepareStatement($searchQuery);
			} catch (ConnectException $e) {
				throw new LoadException('Failed to perform Model Search due to a MySQL\\ConnectException', null, 0, $e);
			} catch (QueryException $e) {
				throw new LoadException("Failed to perform Model Search due to a MySQL\\QueryException: {$e->getMessage()}", null, 0, $e);
			}

			$stmt->bindParams($whereVars);

			return static::InstantiateClassIterator($stmt);
		}

		protected static function CleanUuid($uuid): string {
			// if of ManagedData type, extract the raw value to work with
			if ($uuid instanceof ManagedData) {
				$uuid = $uuid->rawValue();
			}

			// if we are 16 bytes long, then we are most likely a binary form UUID
			if (strlen($uuid) === 16) {
				$uuid = bin2hex($uuid);
			} else {
				$uuid = str_replace('-', '', $uuid);
			}

			return $uuid;
		}

		/**
		 * @param Statement $statement
		 * @return Iterator
		 */
		protected static function InstantiateClassIterator(Statement $statement): Iterator {
			$classIterator = static::class . 'Iterator';
			if (!class_exists($classIterator)) {
				$classIterator = Iterator::class;
			}

			return new $classIterator($statement, static::class);
		}
	}
