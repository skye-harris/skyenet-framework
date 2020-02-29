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
	$table->addColumn('uuid',Column::TYPE_BINARY, 16, NULL, Column::FLAG_PRI_KEY | Column::FLAG_NOT_NULL);
	$table->addColumn('firstName',Column::TYPE_VARCHAR, 64);
	$table->addColumn('lastName',Column::TYPE_VARCHAR, 64);

	$result = $sql->query($table->getCreateStatement());
