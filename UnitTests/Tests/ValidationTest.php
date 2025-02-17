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
		// Generic
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

		// Booleans

		public function testBoolIsValid(): void {
			$trueVals = ['yes', 'on', 'true', '1', 1];

			foreach ($trueVals AS $trueVal) {
				$value = DataValidator::ForValue($trueVal)
									  ->bool()
									  ->value();

				$this->assertTrue($value);
			}

			$falseVals = ['no', 'off', 'false', '0', 0];

			foreach ($falseVals AS $falseVal) {
				$value = DataValidator::ForValue($falseVal)
									  ->bool()
									  ->value();

				$this->assertFalse($value);
			}
		}

		public function testBoolIsInvalid(): void {
			$notBool = 'hello';

			$this->expectException(Exception::class);
			DataValidator::ForValue($notBool)
						 ->bool()
						 ->value();
		}

		// Dates
		public function testDateIsValid(): void {
			$date = '2012-12-23';

			$value = DataValidator::ForValue($date)
								  ->date()
								  ->valueArrayYMD();

			$this->assertEquals(2012, $value[0]);
			$this->assertEquals(12, $value[1]);
			$this->assertEquals(23, $value[2]);
		}

		public function testDateIsInvalid(): void {
			$date = 'hello';

			$this->expectException(Exception::class);
			DataValidator::ForValue($date)
						 ->date()
						 ->valueArrayYMD();
		}

		// Integers
		public function testIntegerIsValid(): void {
			$int = 5;

			$value = DataValidator::ForValue($int)
								  ->int()
								  ->value();

			$this->assertEquals($value, $int);
		}

		public function testIntegerIsInvalid(): void {
			$int = 7.5;

			$this->expectException(Exception::class);
			DataValidator::ForValue($int)
						 ->int(4, 6)
						 ->value();
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

		// Floats
		public function testFloatIsValid(): void {
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

		// Strings
		public function testStringValidEmailAddress(): void {
			$email = 'alias+test@email.domain.com';

			$value = DataValidator::ForValue($email)
								  ->string()
								  ->emailAddress()
								  ->value();

			$this->assertEquals($value, $email);
		}

		public function testStringInvalidEmailAddress(): void {
			$email = '@email.';

			$this->expectException(Exception::class);
			DataValidator::ForValue($email)
						 ->string()
						 ->emailAddress()
						 ->value();
		}

		public function testStringRegexValidationSuccess(): void {
			$input = 'hello WORLD';

			$value = DataValidator::ForValue($input)
								  ->string()
								  ->matchesPattern('/^([a-z]+) ([A-Z]{5})$/', null, $matches)
								  ->value();

			$this->assertEquals($value, $input);
			$this->assertCount(3, $matches);
			$this->assertEquals('WORLD', $matches[2]);
		}

		public function testStringRegexValidationFailure(): void {
			$input = 'hellO WORLD';

			$this->expectException(Exception::class);
			DataValidator::ForValue($input)
						 ->string()
						 ->matchesPattern('/^[a-z]+ [A-Z]{5}$/')
						 ->value();
		}

		public function testStringLengthWithinRange(): void {
			$string = 'hello world';

			$value = DataValidator::ForValue($string)
								  ->string(10, 12)
								  ->value();

			$this->assertEquals($string, $value);
		}

		public function testStringLengthBelowRange(): void {
			$string = 'hello world';

			$this->expectException(Exception::class);
			DataValidator::ForValue($string)
						 ->string(18, 20)
						 ->value();
		}

		public function testStringLengthAboveRange(): void {
			$string = 'hello world';

			$this->expectException(Exception::class);
			DataValidator::ForValue($string)
						 ->string(2, 4)
						 ->value();
		}

		// JSON Objects
		public function testJsonObjectIsValid(): void {
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

		public function testJsonObjectIsInvalid(): void {
			$json = '{ key: {}';

			$this->expectException(Exception::class);
			DataValidator::ForValue($json)
						 ->jsonObject()
						 ->value();
		}

		// JSON Arrays
		public function testJsonArrayIsValid(): void {
			$array = [1, 2, 3];

			$json = json_encode($array, JSON_THROW_ON_ERROR, 512);

			$value = DataValidator::ForValue($json)
								  ->jsonArray()
								  ->value();

			$this->assertIsArray($value);
			$this->assertCount(count($array), $value);
		}

		public function testJsonArrayIsInvalid(): void {
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