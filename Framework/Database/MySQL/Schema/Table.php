<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 27/09/2019
	 * Time: 5:03 pm
	 */

	namespace Skyenet\Database\MySQL\Schema;

	use Skyenet\Database\MySQL\Connection;
	use Skyenet\Database\MySQL\Exception;

	class Table {
		private string $tableName;

		private array $columnDefs = [];

		public function __construct(string $tableName) {
			$this->tableName = $tableName;
		}

		public static function From(string $tableName): self {
			$table = new static($tableName);

			$connection = Connection::getInstance();
			$tableRes = $connection->query("SHOW COLUMNS FROM {$tableName}");

			while ($tableRow = $tableRes->fetch_assoc()) {
				$column = new Column();
				$column->name = $tableRow['Field'];
				$column->default = $tableRow['Default'];

				if ($tableRow['Key'] === 'PRI') {
					$column->flags |= Column::FLAG_PRI_KEY;
				}

				if ($tableRow['Null'] === 'NO') {
					$column->flags |= Column::FLAG_NOT_NULL;
				}

				if (!preg_match('/^([a-z]*)(\((\d+(,\d+)?)\))?(.*)?$/i', strtoupper($tableRow['Type']), $matches)) {
					throw new Exception("Failed to determine column type: Column '{$column->name}' with type '{$tableRow['Type']}'");
				}

				$typeName = $matches[1];
				$typeSize = $matches[3];
				$typeFlags = $matches[5];

				$type = Column::MAP_NAMES_TO_TYPES[$typeName] ?? null;

				if (!$type) {
					throw new Exception("Failed to determine column type: Column '{$column->name}' with type '{$typeName}'");
				}

				$column->type = $type;
				$column->size = $typeSize;

				if ($typeFlags) {
					$typeFlags = explode(' ', $typeFlags);
					foreach ($typeFlags AS $typeFlag) {
						if ($flag = Column::MAP_FLAGS[$typeFlag]) {
							$column->flags |= $flag;
						}
					}
				}

				$table->addColumnDef($column);
			}

			return $table;
		}

		public function setTableName(string $tableName): void {
			$this->tableName = $tableName;
		}

		public function addColumn(string $name, int $type, int $length = 0, $default = NULL, int $flags = 0): self {
			$columnDef = new Column();
			$columnDef->name = $name;
			$columnDef->type = $type;
			$columnDef->default = $default;
			$columnDef->size = $length;
			$columnDef->flags = $flags;
			$columnDef->dirty = true;

			$this->columnDefs[$name] = $columnDef;

			return $this;
		}

		public function dropColumn(string $name): self {
			if ($column = $this->columnDefs[$name] ?? null) {
				/* @var $column Column */

				$column->dirty = true;
				$column->drop = true;
			}

			return $this;
		}

		protected function addColumnDef(Column $column): void {
			$this->columnDefs[] = $column;
		}

		public function getAlterStatement(): string {
			$output = "ALTER TABLE {$this->tableName} ";

			$columns = [];
			foreach ($this->columnDefs AS $columnDef) {
				/* @var $columnDef Column */

				if (!$columnDef->dirty) {
					continue;
				}

				if ($columnDef->drop) {
					$columns[] = "DROP COLUMN {$columnDef->name}";
				} else {
					$typeName = $this->typeNames[$columnDef->type] ?? null;
					$columnArr = [
						$columnDef->name,
						$typeName,
						$columnDef->size ? "({$columnDef->size})" : null,
					];

					if ($columnDef->default !== FALSE) {
						if ($columnDef->default === null) {
							if (!($columnDef->flags & Column::FLAG_NOT_NULL)) {
								$val = 'DEFAULT NULL';
							}
						} else {
							$val = "DEFAULT '{$columnDef->default}'";
						}

						$columnArr[] = $val;
					}

					if ($columnDef->flags) {
						foreach (Column::MAP_FLAG_STRINGS AS $key => $part) {
							if ($columnDef->flags & $key) {
								$columnArr[] = $part;
							}
						}
					}

					if ($columnDef->columnExists) {
						$columns[] = 'ALTER COLUMN ' . implode(' ', array_filter($columnArr));
					} else {
						$columns[] = 'ADD ' . implode(' ', array_filter($columnArr));
					}
				}

				$columnDef->dirty = false;
			}
			$output .= implode(', ', array_filter($columns));

			return $output . ';';
		}

		public function getCreateStatement(): string {
			$output = "CREATE TABLE `{$this->tableName}` (";

			$primaryKeys = [];
			$columns = [];
			foreach ($this->columnDefs AS $columnDef) {
				/* @var $columnDef Column */

				$typeName = Column::MAP_TYPES_TO_NAMES[$columnDef->type] ?? null;
				$columnArr = [
					"`{$columnDef->name}`",
					$typeName . ($columnDef->size ? "({$columnDef->size})" : null)
				];

				if ($columnDef->flags) {
					foreach (Column::MAP_FLAG_STRINGS AS $key => $part) {
						if ($columnDef->flags & $key) {
							if ($key === Column::FLAG_PRI_KEY) {
								$primaryKeys[] = $columnDef->name;
							} else {
								$columnArr[] = $part;
							}
						}
					}
				}

				if ($columnDef->default !== FALSE) {
					$val = null;

					if ($columnDef->default === null) {
						if (!($columnDef->flags & Column::FLAG_NOT_NULL)) {
							$val = 'DEFAULT NULL';
						}
					} else {
						$val = "DEFAULT '{$columnDef->default}'";
					}

					if ($val) {
						$columnArr[] = $val;
					}
				}

				$columns[] = implode(' ', array_filter($columnArr));

				$columnDef->columnExists = true;
				$columnDef->dirty = false;
			}

			if (count($primaryKeys)) {
				$keys = array_map(static function($key) { return "`{$key}`"; }, $primaryKeys);
				$keys = implode(', ',$keys);

				$columns[] = "primary key ({$keys})";
			}

			$output .= implode(', ', array_filter($columns)) . ')';

			return $output . ' ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;';
		}

		// Reset the dirty flag on all column definitions
		public function unDirty(): void {
			foreach ($this->columnDefs AS $columnDef) {
				/* @var $columnDef Column */
				$columnDef->dirty = false;
			}
		}
	}