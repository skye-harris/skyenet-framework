<?php
	namespace App\Model\Crescent\User;

	use Skyenet\Database\MySQL\Statement;
	use Skyenet\Exception;
	use Skyenet\ManagedData;
	use Skyenet\Model\Iterator;
	use Skyenet\Model\LoadException;
	use Skyenet\Model\Model;
	use Skyenet\Security\Security;


	/**
	 * Class User
	 *
	 * @package Model\Crescent\User
	 *
	 * @property ManagedData $firstName
	 * @property ManagedData $lastName
	 * @property ManagedData $emailAddress
	 * @property ManagedData $password
	 * @property ManagedData $phone
	 * @property ManagedData $mobilePhone
	 * @property ManagedData $status
	 * @property ManagedData $about
	 * @property ManagedData $position
	 * @property ManagedData $accessRightsMask
	 *
	 * @method static User LoadByUuid(string|ManagedData $uuid)
	 * @method static UserIterator LoadEx(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null, ?int $limit = null)
	 * @method static User LoadOne(?string $whereString = null, ?array $whereVariables = null, ?string $orderBy = null)

	 */
	class User extends Model {
		public CONST STATUS_DISABLED = 0;
		public CONST STATUS_ENABLED = 1;

		public const EVENT_PRE_SAVE = 'USER:PRE_SAVE';
		public const EVENT_POST_SAVE = 'USER:POST_SAVE';
		public const EVENT_PRE_DELETE = 'USER:PRE_DELETE';
		public const EVENT_POST_DELETE = 'USER:POST_DELETE';
		public const EVENT_POST_LOAD = 'USER:POST_LOAD';

		public CONST TEXT_STATUS = [
			self::STATUS_ENABLED => 'Enabled',
			self::STATUS_DISABLED => 'Disabled',
		];

		/* publicly declared variables */

		protected array $_variableMap = [
			'firstName' => null,
			'lastName' => null,
			'emailAddress' => null,
			'password' => null,
			'phone' => null,
			'mobilePhone' => null,
			'status' => null,
			'about' => null,
			'position' => null,
			'accessRightsMask' => null,
		];

		/**
		 * @param string $emailAddress
		 * @return User
		 * @throws LoadException
		 */
		public static function LoadByEmailAddress(string $emailAddress): User {
			$user = static::LoadOne('emailAddress=?', [$emailAddress]);

			if (!$user) {
				throw new LoadException('No user account found under the supplied email address', 'Username or password incorrect');
			}

			return $user;
		}

		/**
		 * @param string $password
		 * @throws UserLoginException
		 */
		public function attemptLogin(string $password): void {
			if (!Security::VerifyPassword($password, $this->password->rawValue())) {
				throw new UserLoginException('Password not correct', 'Username or password incorrect');
			}
		}

		public function getFullName(): ManagedData {
			return new ManagedData(trim("{$this->firstName->rawValue()} {$this->lastName->rawValue()}"));
		}
	}

	class UserIterator extends Iterator {
		public function __construct(Statement $statement) {
			parent::__construct($statement, User::class);
		}

		public function current(): ?User {
			return parent::current();
		}
	}

	class UserLoginException extends Exception {
	}