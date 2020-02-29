<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 4:17 pm
	 */
	namespace UnitTests;

	use PHPUnit\Framework\TestCase;
	use Skyenet\Database\MySQL\Connection;

	abstract class UnitTest extends TestCase {
		protected function setUp(): void {
			parent::setUp();

			$sql = Connection::getInstance();
			$sql->beginTransaction();
		}

		protected function tearDown(): void {
			parent::tearDown();

			$sql = Connection::getInstance();
			$sql->rollbackTransaction();
		}
	}