<?php
	// Functions related to site security
	namespace Skyenet\Security;

	use JKingWeb\DrUUID\UUID;
	use Skyenet\ManagedData;
	use Skyenet\Skyenet;

	class Security {
		protected function __construct() {
		}

		public static function IsDeveloper(): bool {
			//todo: fix
			return PHP_SAPI === 'cli' || (strpos($_SERVER['REMOTE_ADDR'], '192.168.1.') === 0);
		}

		public static function DeveloperEcho($content): void {
			if (self::IsDeveloper()) {
				if ($content instanceof ManagedData) {
					print_r($content->rawValue());
				}
				else {
					print_r($content);
				}

			}
		}

		public static function HTMLEntities($content): ?string {
			return htmlentities($content, ENT_QUOTES);
		}

		// Check if the parameter is a valid email address
		public static function ValidateEmail(string $email): bool {
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}

		// Range check a value
		public static function RangeCheck(float $min, float $max, float $val, bool $inclusive = true): bool {
			return $inclusive ? ($val >= $min && $val <= $max) : ($val > $min && $val < $max);
		}

		// Is a value within a certain range?
		public static function BoundRangeValue(float $min, float $max, float $val): float {
			return ($val > $min) ? ($val < $max ? $val : $max) : $min;
		}

		/**
		 * @param int $length
		 * @return string
		 * @throws Exception
		 */
		private static function GenerateRandomBytes(int $length): string {
			try {
				return random_bytes($length);
			} catch (\Exception $e) {
				/** @noinspection CryptographicallySecureRandomnessInspection */
				$bytes = openssl_random_pseudo_bytes($length, $cryptoStrong);

				if (!$cryptoStrong || $bytes === false) {
					throw new Exception("Failed to generate random_bytes with exception: {$e->getMessage()}", null, 0, $e);
				}

				return $bytes;
			}
		}

		/**
		 * @return string
		 * @throws Exception
		 */
		public static function GeneratePasswordSalt():string {
			return self::GenerateRandomBytes(32);
		}

		/**
		 * @param string $password
		 * @return string
		 * @throws Exception
		 */
		public static function HashPassword(string $password): string {
			$result = password_hash($password,PASSWORD_BCRYPT);

			if ($result === FALSE) {
				throw new Exception('HashPassword failed - investigate!');
			}

			return $result;
		}

		public static function VerifyPassword(string $password, string $passwordHash): bool {
			return password_verify($password, $passwordHash);
		}

		/**
		 * Generate a CSRF token for the client
		 *
		 * @return string
		 * @throws Exception
		 */
		public static function GenerateCSRF(): string {
			try {
				// Todo: properly
				return base64_encode(random_bytes(32));
			} catch (\Exception $e) {
				throw new Exception("random_bytes threw an \Exception: {$e->getMessage()}", null, 0, $e);
			}
		}

		/**
		 * Throws on validation failure
		 *
		 * @throws InvalidCsrfTokenException
		 */
		public static function ValidateCSRF(): void {
			if (PHP_SAPI === 'cli') {
				return;
			}

			$headers = getallheaders();

			if (!isset($headers['Csrf_Token'])) {
				throw new InvalidCsrfTokenException('Client did not provide a CSRF token in their request');
			}

			if (!isset($_SESSION['CSRF_TOKEN'])) {
				throw new InvalidCsrfTokenException('Clients session data does not contain a CSRF token');
			}

			if (urldecode($headers['Csrf_Token']) !== $_SESSION['CSRF_TOKEN']) {
				setcookie('CSRF_TOKEN',$_SESSION['CSRF_TOKEN'], 0, '/');
				//skyeserenaharris@gmail.com

				throw new InvalidCsrfTokenException('Client-provided CSRF token does not match the CSRF token within their session data');
			}
		}

		// Set up our session security data

		/**
		 * @throws Exception
		 */
		public static function SetupSessionSecurity(): void {
			if (isset($_SESSION['CSRF_TOKEN'], $_COOKIE['CSRF_TOKEN']) && $_COOKIE['CSRF_TOKEN'] === $_SESSION['CSRF_TOKEN']) {
				return;
			}

			$_SESSION['CSRF_TOKEN'] = self::GenerateCSRF();
			//$_SESSION['ORIGINAL_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
			$_SESSION['SESSION_STARTED'] = time();

			setcookie('CSRF_TOKEN',$_SESSION['CSRF_TOKEN'], 0, '/');
			$_SESSION['SESSION_SETUP'] = true;
		}

		// Has something changed that is unlikely to have changed within a regular users session?
		public static function ValidateSession(): bool {
			//$originalUserAgent = $_SESSION['ORIGINAL_USER_AGENT'] ?? null;
			//$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

			// Disabled as this causes issues with using dev tools
			//if ($originalUserAgent !== $currentUserAgent)
			//	return false;

			return true;
		}

		// Destroy our session
		public static function DestroySession(): void {
			$_SESSION = [];
			session_regenerate_id(true);
			session_destroy();
		}

		// Generate and return a v4 UUID
		public static function UUID(bool $withSeparators = true): string {
			$uuid = UUID::mint(1,Skyenet::$CONFIG['SERVER_MAC']);

			if (!$withSeparators) {
				$uuid = str_replace('-', '', $uuid);
			}

			return $uuid;
		}
	}