<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 29/02/2020
	 * Time: 5:31 pm
	 */

	use Skyenet\Database\MySQL\Schema\Column;
	use Skyenet\Database\MySQL\Schema\Table;

	$table = new Table('TestModel');
	$table->dropIfExists();

	$table->uuid('uuid', null, false, Column::FLAG_PRI_KEY);
	$table->varchar('firstName');
	$table->varchar('lastName');

	$table->create();