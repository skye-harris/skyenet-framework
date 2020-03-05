<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 1/03/2020
	 * Time: 10:28 am
	 */

	namespace UnitTests\Tests;

	use Skyenet\Controller\Controller;
	use Skyenet\Controller\LoadException;
	use Skyenet\Route\Route;
	use Skyenet\Route\RouteManager;
	use UnitTests\Controller\TestableController;
	use UnitTests\Models\TestModel;
	use UnitTests\UnitTest;

	class ControllerTest extends UnitTest {
		private function createTestRoute(string $path, string $functionName): void {
			$routeManager = RouteManager::getInstance();

			$routeManager->addRoute(RouteManager::GET, $path, TestableController::class, $functionName);
		}

		private function findRoute(string $path): ?Route {
			$routeManager = RouteManager::getInstance();

			return $routeManager->findRoute(RouteManager::GET, array_values(array_filter(explode('/',$path))));
		}

		public function testInstantiateInbuiltRouteParameters(): void {
			$controller = Controller::LoadController(TestableController::class);

			$this->createTestRoute('/int/$id', 'InstantiateInteger');

			$route = $this->findRoute('/int/25');
			$params = $controller->instantiateParametersForRoute($route);
			$this->assertEquals(25,$params[0]);
		}

		public function testInstantiateUrlLoadableRouteParameters(): void {
			$controller = Controller::LoadController(TestableController::class);

			$model = new TestModel();
			$model->firstName = 'Hello';
			$model->lastName = 'World';
			$model->save();
			$modelUuid = $model->getUuid();
			$model = null;

			$this->createTestRoute('/model/$model', 'InstantiateModel');

			$route = $this->findRoute("/model/{$modelUuid}");
			$params = $controller->instantiateParametersForRoute($route);

			$this->assertIsObject($params[0]);
			$this->assertSame(get_class($params[0]), TestModel::class);
			$this->assertEquals($modelUuid,$params[0]->getUuid());
		}

		public function testParameterWithoutTypeDefinition(): void {
			$controller = Controller::LoadController(TestableController::class);

			$this->createTestRoute('/$param', 'ParameterWithoutTypeDefinition');
			$route = $this->findRoute('/hello world');

			$this->expectException(LoadException::class);
			$controller->instantiateParametersForRoute($route);
		}

		public function testUnmatchedVariable(): void {
			$controller = Controller::LoadController(TestableController::class);

			$this->createTestRoute('/$param1', 'UnmatchedVariable');
			$route = $this->findRoute('/hello world');

			$this->expectException(LoadException::class);
			$controller->instantiateParametersForRoute($route);
		}
	}