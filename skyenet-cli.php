<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 5/10/2019
	 * Time: 4:39 pm
	 */

	$vendorPaths = ['/vendor', '/../../'];
	foreach ($vendorPaths AS $vendorPath) {
		$vendorFile = __DIR__ . "{$vendorPath}/autoload.php";

		if (file_exists($vendorFile)) {
			include_once $vendorFile;

			break;
		}
	}

	use CommandLine\CliController;
	use Skyenet\Skyenet;

	$app = Skyenet::getInstance();

	/** @noinspection PhpUnhandledExceptionInspection */
// Our uncaught exception handler will catch any exceptions that make it this far
	$app->initFramework();

	$controller = new CliController();
	$controller->prepareForCli();
