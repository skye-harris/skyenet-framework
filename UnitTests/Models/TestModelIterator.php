<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 3/10/2019
	 * Time: 6:39 pm
	 */

	namespace UnitTests\Models;

	use Skyenet\Database\MySQL\Statement;
	use Skyenet\Model\Iterator;

	class TestModelIterator extends Iterator {
		public function __construct(Statement $statement) {
			parent::__construct($statement, TestModel::class);
		}

		public function current(): ?TestModel {
			return parent::current();
		}
	}