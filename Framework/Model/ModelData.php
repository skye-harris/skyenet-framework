<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 21/03/2019
	 * Time: 6:40 PM
	 */

	namespace Skyenet\Model;

	use Skyenet\Database\MySQL\ConnectException;
	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Database\MySQL\QueryException;
	use Skyenet\Exception;
	use Skyenet\ManagedData;
	use Skyenet\Skyenet;

	/**
	 * All values stored in our ModelData are encrypted at the database level
	 *
	 * @property ManagedData name
	 * @property ManagedData value
	 */
	class ModelData extends Model {
		public const EVENT_PRE_SAVE = 'MODEL_DATA:PRE_SAVE';
		public const EVENT_POST_SAVE = 'MODEL_DATA:POST_SAVE';
		public const EVENT_PRE_DELETE = 'MODEL_DATA:PRE_DELETE';
		public const EVENT_POST_DELETE = 'MODEL_DATA:POST_DELETE';
		public const EVENT_POST_LOAD = 'MODEL_DATA:POST_LOAD';

		protected array $_variableMap = [
			'name' => null,
			'value' => null,
		];

		/**
		 * ModelData constructor.
		 *
		 * @param Model  $objectModel
		 * @param string $key
		 * @throws LoadException
		 */
		public function __construct(Model $objectModel, string $key) {
			parent::__construct();

			try {
				// the key exists so this will always pass
				$this->name = $key;
				$this->_uuid = $objectModel->getUuid();

				$sql = Connection::getInstance();

				$tableName = static::TableName();
				$passPhrase = Skyenet::$CONFIG['DATABASE_ENCRYPTION_PASSPHRASE'];

				/** @noinspection SqlResolve */
				$res = $sql->prepareStatement("SELECT AES_DECRYPT(`value`, UNHEX(SHA2(?,512))) AS `value` FROM `{$tableName}` WHERE `uuid`=UNHEX(?) AND `name`=? LIMIT 1")
						   ->bindParams($passPhrase, $objectModel->getUuid(), $key)
						   ->execute();

				if ($row = $res->fetch_assoc()) {
					$this->value = $row['value'];
					$this->_isNew = false;
				}
			} catch (Exception $e) {
				throw new LoadException("Failed to load ModelData to to exception: {$e->getMessage()}", null, 0, $e);
			}
		}

		/**
		 * @throws SaveException
		 */
		public function save(): void {
			try {
				$sql = Connection::getInstance();
			} catch (ConnectException $e) {
				throw new SaveException("Failed to save object due to a MySQL\ConnectException", null, 0, $e);
			}

			$tableName = static::TableName();
			$passPhrase = Skyenet::$CONFIG['DATABASE_ENCRYPTION_PASSPHRASE'];

			/** @noinspection SqlResolve */
			$query = "INSERT INTO `{$tableName}` (`uuid`,`name`,`value`) VALUES (UNHEX(?),?,AES_ENCRYPT(?, UNHEX(SHA2(?,512)))) ON DUPLICATE KEY UPDATE value=AES_ENCRYPT(?, UNHEX(SHA2(?,512)));";

			try {
				$res = $sql->prepareStatement($query)
						   ->bindParams($this->_uuid, $this->_variableMap['name'], $this->_variableMap['value'], $passPhrase, $this->_variableMap['value'], $passPhrase)
						   ->execute();

				$this->_isNew = false;
				$this->_dirtyVars = [];
				$res->Close();
			} catch (QueryException $e) {
				throw new SaveException("Failed to save object due to a MySQL\QueryException: {$e->getMessage()}", null, 0, $e);
			}
		}

		public function delete(): void {
			if (!$this->_isNew) {

				try {
					$sql = Connection::getInstance();
				} catch (ConnectException $e) {
					throw new SaveException('Failed to delete object due to a MySQL\\ConnectException', null, 0, $e);
				}

				try {
					/** @noinspection SqlResolve */
					$sql->prepareStatement("DELETE FROM `{$this::TableName()}` WHERE `uuid`=UNHEX(?) AND `name`=? LIMIT 1")
						->bindParams($this->_uuid, $this->_variableMap['name'])
						->execute();

					$this->_isNew = true;
				} catch (QueryException $e) {
					throw new SaveException('Failed to delete object due to a MySQL\\QueryException', null, 0, $e);
				}
			}
		}

		public function set($value): self {
			$this->value = $value;

			return $this;
		}

		/**
		 * @param string|ManagedData $uuid
		 * @return ModelData
		 * @throws LoadException
		 */
		public static function LoadByUuid($uuid): Model {
			throw new LoadException('Use \'ModelData::Load\' to load object data');
		}

		public static function LoadEx(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null, ?int $limit = null): Iterator {
			throw new LoadException('Use \'ModelData::Load\' to load object data');
		}
	}