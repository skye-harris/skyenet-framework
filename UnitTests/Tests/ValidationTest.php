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
			$value = DataValidator::ForValue(null, true)
								  ->value();

			$this->assertNull($value);
		}

		public function testNullDataNotAllowed(): void {
			$this->expectException(Exception::class);
			DataValidator::ForValue(null, false)
						 ->value();
		}

		public function testValidInteger(): void {
			$int = 5;

			$value = DataValidator::ForValue($int)
								  ->int()
								  ->value();

			$this->assertEquals($value, $int);
		}

		public function testIntegerWithinRange(): void {
			$int = 5;

			$value = DataValidator::ForValue($int)
								  ->int(4, 6)
								  ->value();

			$this->assertEquals($int, $value);
		}

		public function testIntegerBelowRange(): void {
			$int = 3;

			$this->expectException(Exception::class);
			DataValidator::ForValue($int)
						 ->int(4, 6)
						 ->value();
		}

		public function testIntegerAboveRange(): void {
			$int = 7;

			$this->expectException(Exception::class);
			DataValidator::ForValue($int)
						 ->int(4, 6)
						 ->value();
		}

		public function testInvalidInteger(): void {
			$int = 7.5;

			$this->expectException(Exception::class);
			DataValidator::ForValue($int)
						 ->int(4, 6)
						 ->value();
		}

		public function testValidFloat(): void {
			$float = 5.5;

			$value = DataValidator::ForValue($float)
								  ->float()
								  ->value();

			$this->assertEquals($value, $float);
		}

		public function testFloatWithinRange(): void {
			$float = 5.5;

			$value = DataValidator::ForValue($float)
								  ->float(5, 6)
								  ->value();

			$this->assertEquals($float, $value);
		}

		public function testFloatBelowRange(): void {
			$float = 5.5;

			$this->expectException(Exception::class);
			DataValidator::ForValue($float)
						 ->float(5.6, 6)
						 ->value();
		}

		public function testFloatAboveRange(): void {
			$float = 5.5;

			$this->expectException(Exception::class);
			DataValidator::ForValue($float)
						 ->float(4, 5.4)
						 ->value();
		}

		public function testValidEmailAddress(): void {
			$email = 'alias+test@email.domain.com';

			$value = DataValidator::ForValue($email)
								  ->string()
								  ->emailAddress()
								  ->value();

			$this->assertEquals($value, $email);
		}

		public function testInvalidEmailAddress(): void {
			$email = '@email.';

			$this->expectException(Exception::class);
			DataValidator::ForValue($email)
						 ->string()
						 ->emailAddress()
						 ->value();
		}

		public function testValidJsonObject(): void {
			$object = [
				'key' => 'val'
			];

			$json = json_encode($object, JSON_THROW_ON_ERROR, 512);

			$value = DataValidator::ForValue($json)
								  ->jsonObject()
								  ->value();

			$this->assertIsObject($value);
			$this->assertObjectHasAttribute('key', $value);
			$this->assertEquals($object['key'], $value->key);
		}

		public function testInvalidJsonObject(): void {
			$json = '{ key: {}';

			$this->expectException(Exception::class);
			DataValidator::ForValue($json)
						 ->jsonObject()
						 ->value();
		}

		public function testValidJsonArray(): void {
			$array = [1,2,3];

			$json = json_encode($array, JSON_THROW_ON_ERROR, 512);

			$value = DataValidator::ForValue($json)
								  ->jsonArray()
								  ->value();

			$this->assertIsArray($value);
			$this->assertCount(count($array), $value);
		}

		public function testInvalidJsonArray(): void {
			$object = [
				'key' => 'value'
			];

			$json = json_encode($object, JSON_THROW_ON_ERROR, 512);

			$this->expectException(Exception::class);
			DataValidator::ForValue($json)
						 ->jsonArray()
						 ->value();
		}
	}