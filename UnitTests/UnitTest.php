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
	use Skyenet\Route\RouteManager;

	abstract class UnitTest extends TestCase {
		protected function setUp(): void {
			$sql = Connection::getInstance();
			$sql->beginTransaction();
		}

		protected function tearDown(): void {
			$sql = Connection::getInstance();
			$sql->rollbackTransaction();

			RouteManager::getInstance()->clearRoutes();
		}
	}