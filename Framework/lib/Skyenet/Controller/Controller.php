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
	use Skyenet\Model\Model;
	use Skyenet\Route\Route;
	use Skyenet\Skyenet;

	abstract class Controller {
		protected Skyenet $mvc;

		public const EVENT_PRE_RUN = 'CONTROLLER:PRE_RUN';
		public const EVENT_POST_RUN = 'CONTROLLER:POST_RUN';

		public function __construct() {
			$this->mvc = Skyenet::getInstance();
		}

		// this has been moved here so that a controller can override behaviour if required

		/**
		 * @param Route $route
		 * @throws LoadException
		 */
		public function runRoute(Route $route): void {
			// Broadcast our PRE_RUN event.. if we are returned false here, then bail-out
			if (EventManager::BroadcastEvent($event = new Event(static::EVENT_PRE_RUN, $this, null, true))) {
				throw new LoadException('Route invocation rejected due to a cancelled EVENT_PRE_RUN event', $event->getCancellationUserFriendlyMessage());
			}

			try {
				$reflectionClass = new ReflectionClass(get_class($this));
				$reflectionMethod = $reflectionClass->getMethod($route->functionName);
				$reflectionParameters = $reflectionMethod->getParameters();

				$bindVars = [];
				foreach ($reflectionParameters AS $reflectionParameter) {
					$paramName = $reflectionParameter->getName();
					$paramType = $reflectionParameter->getType();

					if (!array_key_exists($paramName, $route->matchVars) || !$paramType) {
						$bindVar = $route->matchVars[$paramName] ?? null;

						if (!$bindVar && !$reflectionParameter->allowsNull()) {
							throw new LoadException("Unable to match variable {$paramName}");
						}

						$bindVars[]= $bindVar;

						continue;
					}

					if ($paramType->isBuiltin()) {
						$bindVars[] = $route->matchVars[$paramName];
					} else {
						$paramClass = $reflectionParameter->getClass();
						if ($paramClass->isSubclassOf(Model::class)) {
							try {
								// function is mandatory for Models
								$loadMethod = $paramClass->getMethod('LoadByUuid');

								$bindVars[] = $loadMethod->invoke(null, $route->matchVars[$paramName]);
							}
							/** @noinspection PhpRedundantCatchClauseInspection */
							catch (\Skyenet\Model\LoadException $exception) {
								if (!$reflectionParameter->allowsNull()) {
									throw new LoadException("Failed to instantiate {$paramClass->getName()} for parameter {$paramName}", null, 0, $exception);
								}

								$bindVars[] = null;
							}
						} else {
							if (!$reflectionParameter->allowsNull()) {
								$modelClass = Model::class;

								throw new LoadException("Unable to instantiate class {$paramClass->getName()} for parameter {$paramName}: Object must subclass {$modelClass}");
							}

							$bindVars[] = null;
						}
					}
				}

				call_user_func_array([$this,$route->functionName],$bindVars);
			} catch (ReflectionException $e) {
				throw new LoadException("Failed to load Route method: {$e->getMessage()}", null, 0, $e);
			}

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