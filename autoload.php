<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 6/10/2019
	 * Time: 1:47 pm
	 */

	define('SKYENET_INCLUDES', [
		__DIR__ . '/Skyenet Framework',
		__DIR__ . '/lib',
	]);

	define('SKYENET_NAMESPACES', [
		'Skyenet',
		'App',
		'Console'
	]);

	set_include_path(get_include_path() . ';' . implode(';', SKYENET_INCLUDES));

	spl_autoload_register(static function (string $class) {
		if ($class[0] === '/')
			$class = substr($class, 1);

		$classPath = str_replace('\\', '/', $class);
		$primaryNamespace = substr($classPath, 0, strpos($classPath, '/'));

		if (!in_array($primaryNamespace, SKYENET_NAMESPACES,true)) {
			return false;
		}

		foreach (SKYENET_INCLUDES AS $includePath) {
			$testPath = "{$includePath}/{$classPath}.php";

			if (file_exists($testPath)) {
				/** @noinspection PhpIncludeInspection */
				require $testPath;

				return true;
			}
		}

		return false;
	});
