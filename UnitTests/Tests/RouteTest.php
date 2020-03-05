<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 1/03/2020
	 * Time: 10:19 am
	 */

	namespace UnitTests\Tests;

	use Skyenet\Route\RouteManager;
	use UnitTests\UnitTest;

	class RouteTest extends UnitTest {
		public function testFindRoute(): void {
			$routeManager = RouteManager::getInstance();

			$routeManager->addRoute(RouteManager::GET, '/hello', '', 'Route 1');
			$routeManager->addRoute(RouteManager::GET, '/hello/world', '', 'Route 2');
			$routeManager->addRoute(RouteManager::POST, '/hello/world', '', 'Route 3');

			$route = $routeManager->findRoute('GET', ['hello', 'world']);

			$this->assertEquals('Route 2', $route->functionName);
		}
	}