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

	class SecondModelIterator extends Iterator {
		public function __construct(Statement $statement) {
			parent::__construct($statement, SecondModel::class);
		}

		public function current(): ?SecondModel {
			return parent::current();
		}
	}