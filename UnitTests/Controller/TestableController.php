<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 1/03/2020
	 * Time: 10:27 am
	 */

	namespace UnitTests\Controller;

	use UnitTests\Models\TestModel;

	class TestableController extends \Skyenet\Controller\Controller {
		public function InstantiateInteger(int $id): void {

		}

		public function InstantiateModel(TestModel $model): void {

		}
	}