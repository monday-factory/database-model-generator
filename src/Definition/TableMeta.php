<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class TableMeta extends \ArrayIterator
{
	public function __construct(
		array $tableMeta,
	) {
		parent::__construct($tableMeta);
	}

	public static function factory(array $meta): self
	{
		$tableMeta = [];

		foreach ($meta as $columnName => $columnMeta) {
			$tableMeta[$columnName] = ColumnOptions::factory($columnMeta ?? []);
		}

		return new self($tableMeta);
	}

	public function getColumn(string $name): ColumnOptions
	{
		return @$this->offsetGet($name) ?? ColumnOptions::factory([]);
	}
}
