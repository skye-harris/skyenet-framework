<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 4/03/2020
	 * Time: 9:27 pm
	 */

	namespace UnitTests\Tests;

	use Skyenet\ManagedData;
	use Skyenet\Security\Security;
	use UnitTests\UnitTest;

	class ManagedDataTest extends UnitTest {
		public function testHtmlContent():void {
			$html = '<script>alert("hello world");</script>';
			$encodedHtml = Security::HTMLEntities($html);

			$managedData = new ManagedData($html);

			$this->assertEquals($html, $managedData->rawValue());
			$this->assertEquals($encodedHtml, $managedData->htmlSafe());
			$this->assertEquals($encodedHtml, (string)$managedData);	// __toString
		}

		public function testComparisons():void {
			$testValue = 5;
			$managedData = new ManagedData($testValue);

			$this->assertTrue($managedData->lessThan($testValue+1));
			$this->assertFalse($managedData->lessThan($testValue));
			$this->assertTrue($managedData->greaterThan($testValue-1));
			$this->assertFalse($managedData->greaterThan($testValue));

			$this->assertTrue($managedData->lessThanOrEqual($testValue));
			$this->assertTrue($managedData->lessThanOrEqual($testValue-1));
			$this->assertFalse($managedData->lessThanOrEqual($testValue+1));

			$this->assertTrue($managedData->greaterThanOrEqual($testValue));
			$this->assertTrue($managedData->greaterThanOrEqual($testValue+1));
			$this->assertFalse($managedData->greaterThanOrEqual($testValue-1));

			$this->assertTrue($managedData->equals($testValue));
			$this->assertFalse($managedData->equals($testValue+1));
		}
	}