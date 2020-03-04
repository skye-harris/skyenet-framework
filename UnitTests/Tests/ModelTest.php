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
	use UnitTests\Models\SecondModel;
	use UnitTests\Models\TestModel;
	use UnitTests\UnitTest;
	use WeakReference;

	class ModelTest extends UnitTest {
		private function createTestModel(array $fields = []): TestModel {
			$modelFields = array_merge([
				'firstName' => 'Hello',
				'lastName' => 'World'
			],$fields);

			$model = new TestModel($modelFields);
			$model->save();

			return $model;
		}

		private function createSecondModel(array $fields = []): SecondModel {
			$modelFields = array_merge([
				'firstName' => 'Hello',
				'lastName' => 'World'
			],$fields);

			$model = new SecondModel($modelFields);
			$model->save();

			return $model;
		}

		public function testCreateModel(): void {
			$this->createTestModel();

			$allTestModels = TestModel::LoadEx();
			$this->assertCount(1, $allTestModels);
		}

		public function testDeleteModel(): void {
			$model = $this->createTestModel();
			$modelUuid = $model->getUuid();
			$model->delete();

			$this->assertTrue($model->isNew());

			$model = null;
			$this->expectException(LoadException::class);
			TestModel::LoadByUuid($modelUuid);
		}

		public function testLoadModelFromCache(): void {
			$model = $this->createTestModel();
			$model->firstName = 'Bob';

			$uuid = $model->getUuid();
			$model2 = TestModel::LoadByUuid($uuid);

			self::assertEquals($model, $model2);
			self::assertEquals($model->firstName->rawValue(), $model2->firstName->rawValue());
		}

		public function testLoadModelNoCache(): void {
			$model = $this->createTestModel();

			$model->firstName = 'Bob';
			$uuid = $model->getUuid();

			$weakRef = WeakReference::create($model);
			$model = null;

			$model2 = TestModel::LoadByUuid($uuid);

			self::assertNull($weakRef->get());
			self::assertNotEquals('Bob', $model2->firstName);
		}

		public function testModelIterator(): void {
			$models = [
				$this->createTestModel()->getUuid(),
				$this->createTestModel()->getUuid(),
				$this->createTestModel()->getUuid(),
			];

			$iteratorModels = TestModel::LoadEx();
			$this->assertCount(count($models), $iteratorModels);

			foreach ($iteratorModels AS $testModel) {
				$this->assertContains($testModel->getUuid(), $models);
			}
		}

		public function testModelData(): void {
			$testVal = random_bytes(8);

			$model = $this->createTestModel();
			$data = new ModelData($model, 'key');
			$data->value = $testVal;
			$data->save();

			$this->assertEquals(false, $data->isDirty());
			$data = null;

			$data2 = new ModelData($model, 'key');
			$this->assertEquals($testVal, $data2->value->rawValue());
		}

		public function testModelRelations(): void {
			$model1 = $this->createTestModel(['firstName' => 'Bob']);
			$model2 = $this->createTestModel(['firstName' => 'Alice']);
			$relationCount = 3;

			/** @var TestModel $model */
			foreach ([$model1,$model2] AS $model) {
				for ($i=0;$i<$relationCount;$i++) {
					$this->createSecondModel([
						'data' => "{$model->firstName} Relation",
						'testModelUuid' => $model->getUuid(true)
					]);
				}
			}

			/** @var TestModel $model */
			foreach ([$model1,$model2] AS $model) {
				$relations = $model->secondModels();
				$this->assertCount($relationCount, $relations);

				foreach ($relations AS $relation) {
					$parentModel = $relation->parentTestModel();

					// model will be loaded from cache, so it will be the same instance
					$this->assertSame($model, $parentModel);
					$this->assertEquals("{$parentModel->firstName} Relation", $relation->data->rawValue());
				}
			}
		}

	}