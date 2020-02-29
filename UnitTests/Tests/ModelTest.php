<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 7:27 pm
	 */

	namespace UnitTests\Tests;

	use Skyenet\Model\ModelData;
	use UnitTests\Models\TestModel;
	use UnitTests\UnitTest;

	class ModelTest extends UnitTest {
		private function createModel(): TestModel {
			$model = new TestModel();
			$model->firstName = 'Hello';
			$model->lastName = 'World';
			$model->save();

			return $model;
		}

		public function testCreateModel(): void {
			$model = $this->createModel();

			$allTestModels = TestModel::LoadEx();
			$this->assertCount(1, $allTestModels);
		}

		public function testLoadModelFromCache(): void {
			$model = $this->createModel();
			$model->firstName = 'Bob';

			$uuid = $model->getUuid();
			$model2 = TestModel::LoadByUuid($uuid);

			self::assertEquals($model, $model2);
			self::assertEquals($model->firstName->rawValue(), $model2->firstName->rawValue());
		}

		public function testLoadModelNoCache(): void {
			$model = $this->createModel();

			$model->firstName = 'Bob';
			$uuid = $model->getUuid();

			$weakRef = \WeakReference::create($model);
			$model = null;

			$model2 = TestModel::LoadByUuid($uuid);

			self::assertNull($weakRef->get());
			self::assertNotEquals('Bob', $model2->firstName);
		}

		public function testModelData(): void {
			$testVal = random_bytes(8);

			$model = $this->createModel();
			$data = new ModelData($model, 'key');
			$data->value = $testVal;
			$data->save();

			$this->assertEquals(false,$data->isDirty());
			$data = null;

			$data2 = new ModelData($model, 'key');
			$this->assertEquals($testVal, $data2->value->rawValue());
		}

	}