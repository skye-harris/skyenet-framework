<?php

	namespace Skyenet\Model;

	use JsonSerializable;
	use ReflectionClass;
	use Skyenet\Cache\ModelCache;
	use Skyenet\Database\MySQL\ConnectException;
	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Database\MySQL\QueryException;
	use Skyenet\Database\MySQL\Search\SearchTokeniser;
	use Skyenet\Database\MySQL\Statement;
	use Skyenet\EventManager\Event;
	use Skyenet\EventManager\EventManager;
	use Skyenet\Http\UrlLoadable;
	use Skyenet\ManagedData;
	use Skyenet\Security\Security;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 13/05/2017
	 * Time: 4:37 PM
	 */
	abstract class Model implements JsonSerializable, UrlLoadable {
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

		protected string $_uuid = '';
		protected bool $_isNew = true;

		/** @var Iterator[] */
		protected array $_toManyCache = [];

		public const EVENT_PRE_SAVE = 'MODEL:PRE_SAVE';
		public const EVENT_POST_SAVE = 'MODEL:POST_SAVE';
		public const EVENT_PRE_DELETE = 'MODEL:PRE_DELETE';
		public const EVENT_POST_DELETE = 'MODEL:POST_DELETE';
		public const EVENT_POST_LOAD = 'MODEL:POST_LOAD';

		// PUBLIC FUNCTIONS

		public function __construct(?array $assocArray = null) {
			$this->_uuid = $assocArray['uuid'] ?? Security::UUID(false);
			$this->_dirtyVars = array_keys($this->_variableMap);

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
			// Test if this is one of our one-to-one getters, and attempt to return the relation model
			if ($oneToOne = static::_oneToOne[$name] ?? null) {
				[$relatedClass, $relationKey] = $oneToOne;

				/** @var Model $relatedClass */
				return $relatedClass::LoadByUuid($this->_variableMap[$relationKey]);
			}

			// Test if this is one of our one-to-many getters, and attempt to return the relation iterator
			if ($oneToMany = static::_oneToMany[$name] ?? null) {
				[$relatedClass, $relationKey] = $oneToMany;

				/** @var Model $relatedClass */
				return $this->_toManyCache[$name] ??= $relatedClass::LoadEx("{$relationKey}=UNHEX(?)", [$this->_uuid]);
			}

			throw new Exception("No method to invoke with name '{$name}'");
		}

		/**
		 * @param $name
		 * @return ManagedData
		 */
		public function __get($name): ManagedData {
			return new ManagedData($this->_variableMap[$name] ?? null);
		}

		/**
		 * @param $name
		 * @param $value
		 * @throws SaveException
		 */
		public function __set(string $name, $value): void {
			// Extract our real value,
			if ($value instanceof ManagedData) {
				$realVal = $value();
			} else if ($value instanceof self) {
				$realVal = $value->getUuid(true);
			} else {
				$realVal = $value;
			}

			// Is this key a part of our model?
			if (!array_key_exists($name, $this->_variableMap)) {
				$myClass = static::class;

				throw new SaveException("Object '{$myClass}' has no property named '{$name}'");
			}

			// Update the value and mark it as dirty, if the value has changed
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

		/**
		 * @param ManagedData|string $uuid
		 * @throws Exception
		 */
		public function setUuid($uuid): void {
			if (!$this->_isNew) {
				throw new Exception('Model UUID can only be changed on NEW/UNSAVED models');
			}

			$this->_uuid = self::CleanUuid($uuid);
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
				$myClass = static::class;
				throw new SaveException("Failed to delete {$myClass} due to a MySQL\\ConnectException", null, 0, $e);
			}

			try {
				// Broadcast our PRE_DELETE event.. if we are returned false here, then bail-out
				// Should we throw an exception here? or return some kind of failure signal?

				if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_DELETE, $this, null, true))) {
					throw new SaveException('Model save rejected due to a cancelled EVENT_PRE_SAVE event', $event->getCancellationUserFriendlyMessage());
				}

				/** @noinspection SqlResolve */
				$res = $sql->prepareStatement("DELETE FROM `{$this::TableName()}` WHERE uuid=UNHEX(?) LIMIT 1")
						   ->bindParams($this->_uuid)
						   ->execute();

				// Flip this as we are no longer backed by our DB
				$this->_isNew = true;

				// If we have an affected row, then broadcast our POST_DELETE event
				if ($res->affected_rows) {
					EventManager::BroadcastEvent(new Event(static::EVENT_POST_DELETE, $this));
				}

			} catch (QueryException $e) {
				$myClass = static::class;

				throw new SaveException("Failed to delete {$myClass} due to a MySQL\\QueryException", null, 0, $e);
			}
		}

		/**
		 * @throws SaveException
		 */
		public function save(): void {
			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				$myClass = static::class;

				throw new SaveException("Failed to save {$myClass} due to a MySQL\\ConnectException", null, 0, $e);
			}

			// Broadcast our PRE_SAVE event.. if we are returned false here, then bail-out
			if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_SAVE, $this, null, true))) {
				throw new SaveException('Model save rejected due to a cancelled EVENT_PRE_SAVE event', $event->getCancellationUserFriendlyMessage());
			}

			// Populate the query with our dirty fields
			$values = [];
			$queryPart = [];
			foreach ($this->_dirtyVars AS $key) {
				if ($key === 'uuid') {
					continue;
				}

				$queryPart[] = !$this->_isNew ? "`{$key}`=?" : $key;

				$values[] = $this->_variableMap[$key];
			}

			// Only make the DB call if we actually have data to be updated
			if (count($values)) {
				$queryPart = implode(',', $queryPart);

				// todo: consider what repercussions there may be if this is changed to an upsert
				if (!$this->_isNew) {
					$query = "# noinspection SqlInsertValues
					UPDATE `{$this::TableName()}` SET {$queryPart} WHERE `uuid`=UNHEX(?) LIMIT 1";
				} else {
					$placeholderPart = implode(',', array_fill(0, count($values), '?'));

					$query = "# noinspection SqlInsertValues
					INSERT INTO `{$this::TableName()}` ({$queryPart},`uuid`) VALUES ({$placeholderPart},UNHEX(?));";
				}

				$values[] = $this->_uuid;

				try {
					$res = $sql->prepareStatement($query)
							   ->bindParams($values)
							   ->execute();

					// Insert our model into the weak-reference cache
					if ($this->_isNew) {
						$this->cacheSelf();
					}

					$this->_isNew = false;

					// If we have an affected row, then broadcast our POST_SAVE event
					if ($res->affected_rows) {
						EventManager::BroadcastEvent(new Event($this::EVENT_POST_SAVE, $this));
					}
				} catch (QueryException $e) {
					$myClass = static::class;
					throw new SaveException("Failed to save {$myClass} due to a MySQL\\QueryException: {$e->getMessage()}", null, 0, $e);
				}
			}
		}

		public function jsonSerialize(): array {
			$myData = $this->_variableMap;
			$myData['uuid'] = $this->getUuid();

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
				$myClass = static::class;
				throw new LoadException("Failed to load {$myClass} due to a MySQL\\ConnectException", null, 0, $e);
			}

			try {
				/** @noinspection SqlResolve */
				$res = $sql->query("SELECT * FROM {$this::TableName()} WHERE uuid=? LIMIT 1 FOR UPDATE;", $binaryUuid);
			} catch (QueryException $e) {
				$myClass = static::class;
				throw new LoadException("Failed to load {$myClass} due to a MySQL\\QueryException", null, 0, $e);
			}

			if ($res->num_rows !== 1) {
				$res->close();
				$myClass = static::class;

				throw new LoadException("Failed to load {$myClass} due to no results returned");
			}

			$row = $res->fetch_assoc();
			$res->Close();

			$this->initialise($row);
			$this->_dirtyVars = [];
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

			// todo: check for collision?
			$this->cacheSelf();

			// Broadcast our POST_LOAD event
			EventManager::BroadcastEvent(new Event(static::EVENT_POST_LOAD, $this));
		}

		protected function cacheSelf(): void {
			ModelCache::Cache($this);
		}

		// PUBLIC STATIC FUNCTIONS

		public static function Events(bool $includeParentalEvents = false): array {
			// Fetch all events from this class and its ancestors

			$class = new ReflectionClass(static::class);
			$eventArray = [];

			while (true) {
				foreach (['EVENT_POST_DELETE', 'EVENT_POST_LOAD', 'EVENT_POST_SAVE', 'EVENT_PRE_SAVE', 'EVENT_PRE_DELETE'] AS $eventName) {
					$eventArray[] = $class->getConstant($eventName);
				}

				$class = $class->getParentClass();

				if (!$includeParentalEvents || !$class) {
					break;
				}
			}

			return $eventArray;
		}

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
				$myClass = static::class;
				throw new LoadException("Failed to load {$myClass} due to a MySQL\\ConnectException", null, 0, $e);
			}

			$wherePart = $whereString ? "WHERE {$whereString}" : null;
			$limitPart = $limit ? "LIMIT {$limit}" : null;
			$orderPart = $orderBy ? "ORDER BY {$orderBy}" : null;

			try {
				$tableName = static::TableName();

				$stmt = $sql->prepareStatement("SELECT * FROM `{$tableName}` {$wherePart} {$orderPart} {$limitPart} FOR UPDATE;");
				if ($whereVariables && count($whereVariables)) {
					$stmt->bindParams($whereVariables);
				}

				return static::InstantiateClassIterator($stmt);
			} catch (QueryException $e) {
				$myClass = static::class;
				throw new LoadException("Failed to load {$myClass} due to a MySQL\\QueryException", null, 0, $e);
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

		/**
		 * @param string $requestString
		 * @return UrlLoadable
		 * @throws LoadException
		 */
		public static function LoadFromRequestString(string $requestString): UrlLoadable {
			return static::LoadByUuid($requestString);
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
		final protected static function LoadWithSearch(array $searchFields, SearchTokeniser $searchTokeniser, ?string $whereString = null, array $whereVariables = null, ?string $orderBy = 'searchRelevanceScore DESC', ?int $limit = null): Iterator {
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
SELECT *, (MATCH({$searchFieldPart}) AGAINST (? IN BOOLEAN MODE)) AS `searchRelevanceScore`

FROM `{$tableName}`

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

		final protected static function CleanUuid($uuid): string {
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
		final protected static function InstantiateClassIterator(Statement $statement): Iterator {
			$classIterator = static::class . 'Iterator';
			if (!class_exists($classIterator)) {
				$classIterator = Iterator::class;
			}

			return new $classIterator($statement, static::class);
		}
	}
