<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 14/06/2018
	 * Time: 7:05 PM
	 */

	namespace Skyenet\Controller;

	// Controller parent class that all page Controllers must extend
	use Error;
	use ReflectionClass;
	use ReflectionException;
	use Skyenet\EventManager\Event;
	use Skyenet\EventManager\EventManager;
	use Skyenet\Http\UrlLoadable;
	use Skyenet\ManagedData;
	use Skyenet\Route\Route;
	use Skyenet\Skyenet;

	abstract class Controller {
		protected Skyenet $skyenet;

		public const EVENT_PRE_RUN = 'CONTROLLER:PRE_RUN';
		public const EVENT_POST_RUN = 'CONTROLLER:POST_RUN';

		public function __construct() {
			$this->skyenet = Skyenet::getInstance();
		}

		public function executeRoute(Route $route, array $parameters): void {
			call_user_func_array([$this, $route->functionName], $parameters);
		}

		/**
		 * @param Route $route
		 * @return array
		 * @throws LoadException
		 */
		final public function instantiateParametersForRoute(Route $route): array {
			$output = [];

			try {
				$reflectionClass = new ReflectionClass(get_class($this));
				$reflectionMethod = $reflectionClass->getMethod($route->functionName);
				$reflectionParameters = $reflectionMethod->getParameters();

				// map our function parameters to the route variables
				foreach ($reflectionParameters AS $reflectionParameter) {
					$paramName = $reflectionParameter->getName();
					$paramType = $reflectionParameter->getType();

					if (!array_key_exists($paramName, $route->matchVars) || !$paramType) {
						$bindVar = $route->matchVars[$paramName] ?? null;

						if (!$bindVar && !$reflectionParameter->allowsNull()) {
							throw new LoadException("Unable to match variable {$paramName}");
						}

						$output[] = new ManagedData($bindVar);

						continue;
					}


					if ($paramType->isBuiltin()) {
						// built-ins are passed-through directly
						$output[] = $route->matchVars[$paramName];
					} else {
						// objects must implement UrlLoadable, so that we can load it and pass through to the function
						$paramClass = $reflectionParameter->getClass();
						if ($paramClass->implementsInterface(UrlLoadable::class)) {
							try {
								$loadMethod = $paramClass->getMethod('LoadFromRequestString');

								$output[] = $loadMethod->invoke(null, $route->matchVars[$paramName]);
							} /** @noinspection PhpRedundantCatchClauseInspection */
							catch (\Skyenet\Model\LoadException $exception) {
								if (!$reflectionParameter->allowsNull()) {
									throw new LoadException("Failed to instantiate {$paramClass->getName()} for parameter {$paramName}", null, 0, $exception);
								}

								$output[] = null;
							}
						} else {
							if (!$reflectionParameter->allowsNull()) {
								$loadableClass = UrlLoadable::class;

								throw new LoadException("Unable to instantiate class {$paramClass->getName()} for parameter {$paramName}: Object must implement {$loadableClass}");
							}

							$output[] = null;
						}
					}

				}
			} catch (ReflectionException $e) {
				throw new LoadException("Failed to load Route method: {$e->getMessage()}", null, 0, $e);
			}

			return $output;
		}

		/**
		 * @param Route $route
		 * @throws LoadException
		 */
		public function prepareForRoute(Route $route): void {
			// Broadcast our PRE_RUN event.. if we are returned false here, then bail-out
			if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_RUN, $this, $route, true))) {
				throw new LoadException('Route invocation rejected due to a cancelled EVENT_PRE_RUN event', $event->getCancellationUserFriendlyMessage());
			}

			// map our function parameters to the route variables
			$bindVars = $this->instantiateParametersForRoute($route);

			// run our route function
			$this->executeRoute($route, $bindVars);

			// Broadcast our POST_RUN event
			EventManager::BroadcastEvent(new Event(static::EVENT_POST_RUN, $this));
		}

		// Instantiate a page controller

		/**
		 * @param string $controllerName
		 * @return Controller
		 * @throws LoadException
		 */
		public static function LoadController(string $controllerName): Controller {
			try {
				$pageController = new $controllerName();
			} catch (Error $error) {
				throw new LoadException("Controller '{$controllerName}' could not be instantiated: {$error->getMessage()}");
			}

			return $pageController;
		}
	}