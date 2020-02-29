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
	 * @package UnitTests\Models\TestModel
	 *
	 * @property ManagedData $firstName
	 * @property ManagedData $lastName
	 *
	 * @method static TestModel LoadByUuid(string|ManagedData $uuid)
	 * @method static TestModelIterator LoadEx(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null, ?int $limit = null)
	 * @method static TestModel LoadOne(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null)
 */
	class TestModel extends Model {
		public const EVENT_PRE_SAVE = 'TEST_MODEL:PRE_SAVE';
		public const EVENT_POST_SAVE = 'TEST_MODEL:POST_SAVE';
		public const EVENT_PRE_DELETE = 'TEST_MODELPRE_DELETE';
		public const EVENT_POST_DELETE = 'TEST_MODEL:POST_DELETE';
		public const EVENT_POST_LOAD = 'TEST_MODEL:POST_LOAD';

		/* publicly declared variables */
		protected array $_variableMap = [
			'firstName' => null,
			'lastName' => null,
		];

	}