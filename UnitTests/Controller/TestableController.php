<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 1/03/2020
	 * Time: 10:27 am
	 */

	namespace UnitTests\Controller;

	use Skyenet\Controller\Controller;
	use UnitTests\Models\TestModel;

	class TestableController extends Controller {
		public function InstantiateInteger(int $id): void {

		}

		public function InstantiateModel(TestModel $model): void {

		}

		public function ParameterWithoutTypeDefinition($param): void {

		}

		public function UnmatchedVariable(int $param1, int $param2):void {

		}
	}