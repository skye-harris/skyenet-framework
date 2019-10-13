<?php
	namespace Skyenet\Cache;

	use Skyenet\ManagedData;
	use Skyenet\Model\Model;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/10/2019
	 * Time: 10:22 am
	 */

	class ModelCache {
		protected static array $_weakReferenceCache = [];

		public static function Cache(Model $model): void {
			static::$_weakReferenceCache[$model->getUuid(true)] = \WeakReference::create($model);
		}

		/**
		 * @param string|ManagedData $uuid
		 * @return Model|null
		 */
		public static function Get($uuid): ?Model {
			$binaryUuid = Model::BinaryUuid($uuid);

			if ($weakReference = static::$_weakReferenceCache[$binaryUuid] ?? null) {
				if ($obj = $weakReference->get()) {
					return $obj;
				}

				unset(static::$_weakReferenceCache[$binaryUuid]);
			}

			return null;
		}

		protected function __construct() {
		}
	}