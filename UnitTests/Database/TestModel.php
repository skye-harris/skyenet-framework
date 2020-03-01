<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 5:31 pm
	 */

	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Database\MySQL\Schema\Column;
	use Skyenet\Database\MySQL\Schema\Table;

	$sql = Connection::getInstance();
	$sql->query('DROP TABLE IF EXISTS `TestModel`');

	$table = new Table('TestModel');
	$table->uuid('uuid', null, false, Column::FLAG_PRI_KEY | Column::FLAG_NOT_NULL);
	$table->string('firstName');
	$table->string('lastName');
	$table->create();

	$result = $sql->query($table->getCreateStatement());
