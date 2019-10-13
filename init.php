<?php
	use App\Controller\ExampleController;
	use Skyenet\Route;
	use Skyenet\Skyenet;

	include_once __DIR__ . '/Framework/autoload.php';
	include_once __DIR__ . '/vendor/autoload.php';

	$routes = Route\RouteManager::getInstance();

	$routes->addRoute(Route\RouteManager::GET, 'pathTo/$variable', ExampleController::class, 'getRequest');
	$routes->addRoute(Route\RouteManager::POST, 'postRequest', ExampleController::class, 'postRequest');

	$app = skyenet::getInstance();

	/** @noinspection PhpUnhandledExceptionInspection */
	// Our uncaught exception handler will catch any exceptions that make it this far
	$app->start();