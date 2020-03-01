<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 27/09/2019
	 * Time: 5:03 pm
	 */

	namespace Skyenet\Database\MySQL\Schema;

	class Column {
		public string $name;
		public int $type;
		public int $size;
		public ?string $default;
		public int $flags;

		public bool $columnExists = false;
		public bool $drop = false;
		public bool $dirty = false;

		public const TYPE_TINYINT = 2;
		public const TYPE_BOOL = 3;
		public const TYPE_SMALLINT = 4;
		public const TYPE_MEDIUMINT = 5;
		public const TYPE_INTEGER = 6;
		public const TYPE_BIGINT = 7;
		public const TYPE_SERIAL = 8;
		public const TYPE_FLOAT = 9;
		public const TYPE_DOUBLE = 10;
		public const TYPE_DECIMAL = 11;
		public const TYPE_NUMERIC = 12;
		public const TYPE_FIXED = 13;

//	dates
		public const TYPE_DATE = 14;
		public const TYPE_DATETIME = 15;
		public const TYPE_TIMESTAMP = 16;
		public const TYPE_TIME = 17;
		public const TYPE_YEAR = 18;

//	strings & binary
		public const TYPE_CHAR = 19;
		public const TYPE_VARCHAR = 20;
		public const TYPE_ENUM = 21;
		public const TYPE_SET = 22;
		public const TYPE_BINARY = 23;
		public const TYPE_VARBINARY = 24;
		public const TYPE_TINYBLOB = 25;
		public const TYPE_BLOB = 26;
		public const TYPE_MEDIUMBLOB = 27;
		public const TYPE_TINYTEXT = 28;
		public const TYPE_TEXT = 29;
		public const TYPE_MEDIUMTEXT = 30;

		public const MAP_TYPES_TO_NAMES = [
			self::TYPE_TINYINT => 'TINYINT',
			self::TYPE_SMALLINT => 'SMALLINT',
			self::TYPE_MEDIUMINT => 'MEDIUMINT',
			self::TYPE_INTEGER => 'INT',
			self::TYPE_BIGINT => 'BIGINT',
			self::TYPE_DECIMAL => 'DECIMAL',

			self::TYPE_DATE => 'DATE',
			self::TYPE_DATETIME => 'DATETIME',
			self::TYPE_TIME => 'TIME',
			self::TYPE_TIMESTAMP => 'TIMESTAMP',

			self::TYPE_CHAR => 'CHAR',
			self::TYPE_VARCHAR => 'VARCHAR',
			self::TYPE_TINYTEXT => 'TINYTEXT',
			self::TYPE_TEXT => 'TEXT',
			self::TYPE_MEDIUMTEXT => 'MEDIUMTEXT',
			self::TYPE_LONGTEXT => 'LONGTEXT',

			self::TYPE_BINARY => 'BINARY',
			self::TYPE_TINYBLOB => 'TINYBLOB',
			self::TYPE_MEDIUMBLOB => 'MEDIUMBLOB',
			self::TYPE_BLOB => 'BLOB',
		];
		
		public const MAP_NAMES_TO_TYPES = [
			'TINYINT' => self::TYPE_TINYINT,
			'SMALLINT' => self::TYPE_SMALLINT,
			'MEDIUMINT' => self::TYPE_MEDIUMINT,
			'INT' => self::TYPE_INTEGER,
			'BIGINT' => self::TYPE_BIGINT,
			'DECIMAL' => self::TYPE_DECIMAL,

			'DATE' => self::TYPE_DATE,
			'DATETIME' => self::TYPE_DATETIME,
			'TIME' => self::TYPE_TIME,
			'TIMESTAMP' => self::TYPE_TIMESTAMP,

			'CHAR' => self::TYPE_CHAR,
			'VARCHAR' => self::TYPE_VARCHAR,
			'TINYTEXT' => self::TYPE_TINYTEXT,
			'TEXT' => self::TYPE_TEXT,
			'MEDIUMTEXT' => self::TYPE_MEDIUMTEXT,
			'LONGTEXT' => self::TYPE_LONGTEXT,

			'BINARY' => self::TYPE_BINARY,
			'TINYBLOB' => self::TYPE_TINYBLOB,
			'MEDIUMBLOB' => self::TYPE_MEDIUMBLOB,
			'BLOB' => self::TYPE_BLOB,
		];

		public const TYPE_LONGTEXT = 31;

		public const FLAG_NOT_NULL = 1;
		public const FLAG_PRI_KEY = 2;
		public const FLAG_UNIQUE_KEY = 4;
		public const FLAG_BLOB = 16;
		public const FLAG_UNSIGNED = 32;
		public const FLAG_ZEROFILL = 64;
		public const FLAG_BINARY = 128;
		public const FLAG_ENUM = 256;
		public const FLAG_AUTO_INCREMENT = 512;
		public const FLAG_TIMESTAMP = 1024;
		public const FLAG_SET = 2048;
		public const FLAG_NUM = 32768;
		public const FLAG_PART_KEY = 16384;
		public const FLAG_GROUP = 32768;
		public const FLAG_UNIQUE = 65536;

		public const MAP_FLAG_STRINGS = [
			self::FLAG_UNSIGNED => 'UNSIGNED',
			self::FLAG_NOT_NULL => 'NOT NULL',
			self::FLAG_AUTO_INCREMENT => 'AUTO_INCREMENT',
			self::FLAG_PRI_KEY => 'PRIMARY KEY',
		];

		public const MAP_FLAGS = [
			'UNSIGNED' => self::FLAG_UNSIGNED,
		];
	}
