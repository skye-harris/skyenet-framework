<?php

	namespace UnitTests\Models;

	use Skyenet\ManagedData;
	use Skyenet\Model\Model;

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 11/02/2019
	 * Time: 7:01 PM
	 */

	/**
	 * Class User
	 *
	 * @package UnitTests\Models\SecondModel
	 *
	 * @property ManagedData $data
	 * @property ManagedData $testModelUuid
	 * @method TestModel parentTestModel()
	 *
	 * @method static SecondModel LoadByUuid(string|ManagedData $uuid)
	 * @method static SecondModelIterator LoadEx(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null, ?int $limit = null)
	 * @method static SecondModel LoadOne(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null)
	 */

	class SecondModel extends Model {
		public const EVENT_PRE_SAVE = 'SECOND_MODEL:PRE_SAVE';
		public const EVENT_POST_SAVE = 'SECOND_MODEL:POST_SAVE';
		public const EVENT_PRE_DELETE = 'SECOND_MODEL';
		public const EVENT_POST_DELETE = 'SECOND_MODEL:POST_DELETE';
		public const EVENT_POST_LOAD = 'SECOND_MODEL:POST_LOAD';

		/* publicly declared variables */
		protected array $_variableMap = [
			'data' => null,
			'testModelUuid' => null,
		];

		public const _oneToOne = [
			'parentTestModel' => [TestModel::class, 'testModelUuid']
		];
	}