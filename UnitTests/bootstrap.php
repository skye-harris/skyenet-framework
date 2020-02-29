<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 4:33 pm
	 */

	namespace UnitTests;

	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Skyenet;

	require_once __DIR__.'/../Framework/Skyenet.php';

	Skyenet::getInstance()->initFramework();

	$sql = Connection::getInstance();

	array_map(static function($input) {
		if (stripos($input, '.php') !== FALSE) {
			try {
				require_once __DIR__ . "/Database/{$input}";
			} catch (\Exception $exception) {
				echo $exception->getMessage().PHP_EOL;

			}
		}
	}, scandir(__DIR__.'/Database'));