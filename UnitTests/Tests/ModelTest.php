<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 7:27 pm
	 */

	namespace UnitTests\Tests;

	use UnitTests\Models\TestModel;
	use UnitTests\UnitTest;

	class ModelTest extends UnitTest {
		public function testCreateModel(): void {
			$model = new TestModel();
			$model->firstName = 'Hello';
			$model->lastName = 'World';
			$model->save();

			$allTestModels = TestModel::LoadEx();
			$this->assertCount(1, $allTestModels);
		}

		public function testLoadModelFromCache(): void {
			$model = new TestModel();
			$model->firstName = 'Hello';
			$model->lastName = 'World';
			$model->save();

			$model->firstName = 'Bob';

			$uuid = $model->getUuid();
			$model2 = TestModel::LoadByUuid($uuid);

			self::assertEquals($model, $model2);
			self::assertEquals($model->firstName->rawValue(), $model2->firstName->rawValue());
		}

		public function testLoadModelNoCache(): void {
			$model = new TestModel();
			$model->firstName = 'Hello';
			$model->lastName = 'World';
			$model->save();

			$model->firstName = 'Bob';
			$uuid = $model->getUuid();

			$weakRef = \WeakReference::create($model);
			$model = null;

			$model2 = TestModel::LoadByUuid($uuid);

			self::assertNull($weakRef->get());
			self::assertNotEquals('Bob', $model2->firstName);
		}

	}