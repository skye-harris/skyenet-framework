<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 2/09/2017
	 * Time: 8:26 PM
	 */

	namespace Skyenet\Route;

	use Skyenet\Traits\Singleton;

	class RouteManager {
		use Singleton;

		public const GET = 1;
		public const POST = 2;
		public const PUT = 3;
		public const DELETE = 4;

		private ?array $getRoutes = [];
		private ?array $postRoutes = [];
		private ?array $putRoutes = [];
		private ?array $delRoutes = [];

		public function clearRoutes(): void {
			$this->getRoutes = $this->postRoutes = $this->putRoutes = $this->delRoutes = [];
		}

		public function addRoute(int $requestType, ?string $urlPart, string $controllerClass, string $functionName): void {
			$route = new Route($urlPart, $controllerClass, $functionName);

			switch ($requestType) {
				case self::POST:
					$this->postRoutes[] = $route;
					break;

				case self::PUT:
					$this->putRoutes[] = $route;
					break;

				case self::DELETE:
					$this->delRoutes[] = $route;
					break;

				default:
					$this->getRoutes[] = $route;
					break;
			}
		}

		/**
		 * @param string $requestType
		 * @param array  $requestParts
		 * @return Route|null
		 */
		public function findRoute(string $requestType, array $requestParts): ?Route {
			switch ($requestType) {
				case 'POST':
					$routeArray = $this->postRoutes;
					break;

				case 'PUT':
					$routeArray = $this->putRoutes;
					break;

				case 'DELETE':
					$routeArray = $this->delRoutes;
					break;

				default:
					$routeArray = $this->getRoutes;
					break;
			}

			/** @var Route $route */
			foreach ($routeArray as $route) {
				if ($route->match($requestParts)) {
					return $route;
				}
			}

			return null;
		}
	}