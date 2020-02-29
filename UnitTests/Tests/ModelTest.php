<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 7:27 pm
	 */

	namespace UnitTests\Tests;

	use Skyenet\Model\LoadException;
	use Skyenet\Model\ModelData;
	use UnitTests\Models\TestModel;
	use UnitTests\UnitTest;

	class ModelTest extends UnitTest {
		private function createModel(array $fields = []): TestModel {
			$modelFields = array_merge([
				'firstName' => 'Hello',
				'lastName' => 'World'
			],$fields);

			$model = new TestModel($modelFields);
			$model->save();

			return $model;
		}

		public function testCreateModel(): void {
			$this->createModel();

			$allTestModels = TestModel::LoadEx();
			$this->assertCount(1, $allTestModels);
		}

		public function testDeleteModel(): void {
			$model = $this->createModel();
			$modelUuid = $model->getUuid();

			$model->delete();

			$this->assertTrue($model->isNew());

			$model = null;

			$this->expectException(LoadException::class);
			TestModel::LoadByUuid($modelUuid);
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

		public function testModelIterator(): void {
			$models = [
				$this->createModel()->getUuid(),
				$this->createModel()->getUuid(),
				$this->createModel()->getUuid(),
			];

			$iteratorModels = TestModel::LoadEx();
			$this->assertCount(count($models), $iteratorModels);

			foreach ($iteratorModels AS $testModel) {
				$this->assertContains($testModel->getUuid(), $models);
			}
		}

		public function testModelData(): void {
			$testVal = random_bytes(8);

			$model = $this->createModel();
			$data = new ModelData($model, 'key');
			$data->value = $testVal;
			$data->save();

			$this->assertEquals(false, $data->isDirty());
			$data = null;

			$data2 = new ModelData($model, 'key');
			$this->assertEquals($testVal, $data2->value->rawValue());
		}

	}