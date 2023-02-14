<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class Meta extends \ArrayIterator
{


	public function __construct(array ...$metas)
	{
		parent::__construct($metas);
	}

	public static function factory(array $meta): self
	{
		foreach ($meta as $tableName => $tableMeta) {
			$meta[$tableName] = TableMeta::factory($tableName, $tableMeta);
		}

		return new self($meta);
	}

	public function getTable(string $name): TableMeta
	{
		return $this->offsetGet($name);
	}
}
