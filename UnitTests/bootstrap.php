<?php

	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 4:33 pm
	 */

	namespace UnitTests;

	use Exception;
	use Skyenet\Skyenet;

	require_once __DIR__ . '/../Framework/Skyenet.php';

	$config = getenv('SKYENET_CONFIG_FILE');

	Skyenet::getInstance()
		   ->initFramework($config);

	array_map(static function ($input) {
		if (stripos($input, '.php') !== FALSE) {
			try {
				/** @noinspection PhpIncludeInspection */
				require_once __DIR__ . "/Database/{$input}";
			} catch (Exception $exception) {
				echo $exception->getMessage() . PHP_EOL;

			}
		}
	}, scandir(__DIR__ . '/Database'));