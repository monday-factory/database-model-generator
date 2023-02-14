<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class TableMeta extends \ArrayIterator
{
	public function __construct(
		private string $tableName,
		array $tableMeta,
	) {
		parent::__construct($tableMeta);
	}

	public static function factory(array $meta): self
	{
		foreach ($meta as $tableName => $columnMeta) {
			$meta[$tableName] = new self(
				$tableName,
				ColumnOptions::factory($columnMeta)
			);
		}

		return new self($meta);
	}

	public function getColumn(string $name): ColumnOptions
	{
		return $this->offsetGet($name);
	}
}
