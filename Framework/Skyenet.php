<?php

	namespace Skyenet;


	// MVC controller singleton

	use Error;
	use Phar;
	use Skyenet\Ajax\AjaxResponse;
	use Skyenet\Controller\Controller;
	use Skyenet\Controller\LoadException;
	use Skyenet\EventManager\Event;
	use Skyenet\EventManager\EventManager;
	use Skyenet\Http\ResponseCodes;
	use Skyenet\Route\Route;
	use Skyenet\Route\RouteManager;
	use Skyenet\Security\Security;
	use Throwable;
	use App\Controller\FourOhFourController;
	use Skyenet\Traits\Singleton;

	class Skyenet {
		use Singleton;

		public const EVENT_UNHANDLED_EXCEPTION = 'APPLICATION:UNHANDLED_EXCEPTION';

		public array $requestParts = [];
		public array $requestVars = [];

		public static array $CONFIG = [];

		// set a redirect header and terminate immediately
		public function redirectTo(string $url, int $responseCode = ResponseCodes::HTTP_MOVED_TEMPORARILY): void {
			$this->setResponseCode($responseCode);
			header("Location: {$url}");

			exit;
		}

		// Set the HTTP response code
		public function setResponseCode(int $responseCode): self {
			http_response_code($responseCode);

			return $this;
		}

		// Handle any uncaught exceptions here.. pretty print the error details or some shit
		public function exception_handler(Throwable $ex): void {
			EventManager::BroadcastEvent(new Event(self::EVENT_UNHANDLED_EXCEPTION, $this, $ex));

			if (static::$CONFIG['DEVELOPER_MODE'] && Security::IsDeveloper()) {
				header('Content-Type: text/html');

				$headers = getallheaders();
				if (!isset($headers['Ajax'])) {
					try {
						$view = new View\View('Developer/UncaughtException');

						$previousExceptions = [];
						$previousException = $ex;
						while ($previousException = $previousException->getPrevious()) {
							$previousExceptionType = get_class($previousException);

							$previousExceptions[] = "<pre class='stack-trace'>
<strong>\\{$previousExceptionType}</strong>
<strong>{$previousException->getFile()}({$previousException->getLine()})</strong>
{$previousException->getMessage()}

{$previousException->getTraceAsString()}</pre>";
						}

						echo $view->buildOutput([
							'Title' => Skyenet::$CONFIG['SITE_TITLE'],
							'ExceptionClass' => '\\' . get_class($ex),
							'ExceptionMessage' => $ex->getMessage(),
							'PreviousExceptionMessage' => $ex->getPrevious() ? $ex->getPrevious()
																				  ->getMessage() : null,
							'StackTrace' => $ex->getTraceAsString(),
							'PreviousExceptions' => implode(PHP_EOL, $previousExceptions),
						], true);
					} catch (View\Exception $e) {
						echo $ex->getTraceAsString();

						exit;
					}
				} else {
					$userMessage = ($ex instanceof Exception) ? $ex->getUserFriendlyMessage() : null;
					$ajaxResponse = new AjaxResponse();
					$ajaxResponse->setMessage(($userMessage ? ($userMessage.'<br>') : null) . $ex->getMessage());

					echo $ajaxResponse;
				}
			}
		}

		/**
		 * @throws Exception
		 */
		protected function loadConfig(): void {
			$configFile = $_SERVER['DOCUMENT_ROOT']. '/../config.json';

			if (!file_exists($configFile))
				throw new Exception('config.json does not exist');

			$config = json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);

			if (!is_array($config))
				throw new Exception('Failed to decode config.json to an associative array');

			static::$CONFIG = array_merge(static::$CONFIG, $config);
		}

		// Run the program!

		/**
		 * @throws Exception
		 * @throws LoadException
		 */
		protected function discoverAndExecuteRoute(): void {
			$routes = RouteManager::getInstance();

			if ($route = $routes->findRoute($_SERVER['REQUEST_METHOD'], $this->requestParts)) {
				$controller = Controller::LoadController($route->controllerClass);

				$this->requestVars = $route->matchVars;
			} else {
				// If that did not work, lets load our 404 controller

				// todo: fix
				$route = new Route('', FourOhFourController::class, 'get');
				$route->matchVars = [];

				//$function = 'get';
				$controller = Controller::LoadController(FourOhFourController::class);
			}

			// free memory used to hold routes
			$routes->free();

			// Run the page controller.

			try {
				$controller->prepareForRoute($route);
			} catch (Error $error) {
				throw new Exception($error->getMessage(), null, 0, $error);
			}
		}

		protected function initAutoloader(): void {
			spl_autoload_register(static function (string $class) {
				if ($class[0] === '/')
					$class = substr($class, 1);

				$namespaces = ['Console','App'];

				if (PHP_SAPI === 'cli') {
					$pathRoot = getcwd();
				} else {
					$pathRoot = "{$_SERVER['DOCUMENT_ROOT']}/..";
				}

				$classPath = str_replace('\\', '/', $class);
				$primaryNamespace = substr($classPath, 0, strpos($classPath, '/'));

				if (!in_array($primaryNamespace, $namespaces,true)) {
					return false;
				}

				$testPath = "{$pathRoot}/{$classPath}.php";

				if (file_exists($testPath)) {
					/** @noinspection PhpIncludeInspection */
					require $testPath;

					return true;
				}

				return false;
			});

		}

		/**
		 * @throws Exception
		 */
		protected function init(): void {
			ini_set('html_errors', false);

			ob_start();
			@set_exception_handler(array($this, 'exception_handler'));

			$this->loadConfig();
			$this->initAutoloader();
		}

		/**
		 * @throws Exception
		 */
		public function initFramework(): void {
			$this->init();
		}

		/**
		 * @param Controller|null $forcedController
		 * @throws Exception
		 */
		public function start(?Controller $forcedController = null): void {
			$this->init();

			// Attempt to instantiate the requested pages controller
			try {
				if ($forcedController) {
					$forcedController->prepareForRoute(null);
				} else {
					// Break apart the request URI
					$urlParts = parse_url($_SERVER['REQUEST_URI']);
					$urlParts = explode('/', $urlParts['path']);
					$this->requestParts = array_filter(array_slice($urlParts, 1),
						static function ($input) {
							return (bool)strlen($input);
						}
					);

					$this->discoverAndExecuteRoute();
				}
			} catch (LoadException $e) {
				throw new Exception("Failed to load Controller class due to a Controller\\LoadException {$e->getMessage()}",null,0,$e);
			}

			@ob_end_flush();
		}

		/**
		 * @throws Exception
		 */
		public static function BuildPhar(): void {
			try {
				$phar = new Phar('skyenet.phar', 0, 'skyenet.phar');
				$phar->buildFromDirectory(__DIR__);
				$phar->setDefaultStub('autoload.php');
			} catch (\Exception $exception) {
				throw new Exception("BuildPhar failed: {$exception->getMessage()}", null, 0, $exception);
			}
		}
	}
