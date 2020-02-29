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
	$sql->query('DROP TABLE IF EXISTS `ModelData`');

	$table = new Table('ModelData');
	$table->addColumn('uuid', Column::TYPE_BINARY, 16, NULL, Column::FLAG_NOT_NULL | Column::FLAG_PRI_KEY);
	$table->addColumn('name', Column::TYPE_VARCHAR, 32, null, Column::FLAG_NOT_NULL | Column::FLAG_PRI_KEY);
	$table->addColumn('value', Column::TYPE_BLOB);

	$result = $sql->query($table->getCreateStatement());
