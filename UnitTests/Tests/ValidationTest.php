<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 8:31 pm
	 */

	namespace UnitTests\Tests;

	use Skyenet\Validation\DataValidator;
	use Skyenet\Validation\Exception;
	use UnitTests\UnitTest;

	class ValidationTest extends UnitTest {
		public function testValidDate(): void {
			$date = '2012-12-23';

			$value = DataValidator::ForValue($date)
								  ->date()
								  ->valueArrayYMD();

			$this->assertEquals(2012, $value[0]);
			$this->assertEquals(12, $value[1]);
			$this->assertEquals(23, $value[2]);
		}

		public function testInvalidDate(): void {
			$date = 'hello';

			$this->expectException(Exception::class);
			DataValidator::ForValue($date)
						 ->date()
						 ->valueArrayYMD();
		}

		public function testNullDataAllowed(): void {
			$value = DataValidator::ForValue(null, true)->value();

			$this->assertNull($value);
		}

		public function testNullDataNotAllowed(): void {
			$this->expectException(Exception::class);
			DataValidator::ForValue(null, false)->value();
		}

	}