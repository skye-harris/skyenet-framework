<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 4:17 pm
	 */

	namespace UnitTests\Tests;

	use PHPUnit\Framework\TestCase;
	use Skyenet\ManagedData;

	class View extends TestCase {
		public function testConditionEquals(): void {
			$view = new \Skyenet\View\View('UnitTests/Views/ConditionEquals');

			$testForTrue = $view->buildOutput(['x' => 1], true);
			$testForFalse = $view->buildOutput(['x' => 5], true);

			$this->assertEquals('<span>X equals ONE</span>', $testForTrue);
			$this->assertNotEquals('<span>X equals ONE</span>', $testForFalse);
		}

		public function testConditionLessThan(): void {
			$view = new \Skyenet\View\View('UnitTests/Views/ConditionLessThan');

			$testForTrue = $view->buildOutput(['x' => 1, 'y' => 2], true);
			$testForFalse = $view->buildOutput(['x' => 5, 'y' => 4], true);

			$this->assertEquals('<span>X less than Y</span>', $testForTrue);
			$this->assertNotEquals('<span>X less than Y</span>', $testForFalse);
		}

		public function testConditionNested(): void {
			$view = new \Skyenet\View\View('UnitTests/Views/ConditionNested');

			$result = $view->buildOutput(['x' => 1, 'y' => 1], true);
			$this->assertEquals('<span>X AND Y == 1</span>', $result);

			$result = $view->buildOutput(['x' => 1, 'y' => 2], true);
			$this->assertEquals('<span>X == 1 BUT NOT Y</span>', $result);

			$result = $view->buildOutput(['x' => 2, 'y' => 1], true);
			$this->assertEquals('<span>Y == 1 BUT NOT X</span>', $result);

			$result = $view->buildOutput(['x' => 2, 'y' => 2], true);
			$this->assertEquals('<span>NEITHER == 1</span>', $result);

		}

		public function testVariableSubstitution(): void {
			$view = new \Skyenet\View\View('UnitTests/Views/VariableSubstitutionTest');

			$this->assertEquals('<span>hello world</span>', $view->buildOutput(['x' => 'hello world']));
		}

		public function testVariableSubstitutionManagedData(): void {
			$view = new \Skyenet\View\View('UnitTests/Views/VariableSubstitutionTest');

			$htmlContent = '<i>TEST</i>';
			$data = new ManagedData($htmlContent);

			$this->assertEquals('<span>&lt;i&gt;TEST&lt;/i&gt;</span>', $view->buildOutput(['x' => $data]));
			$this->assertEquals('<span><i>TEST</i></span>', $view->buildOutput(['x' => $data->rawValue()]));
		}
	}