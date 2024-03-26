<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class Meta extends \ArrayIterator
{

	public function __construct(array $metas)
	{
		parent::__construct($metas);
	}

	public static function factory(array $meta): self
	{
		$tablesMeta = [];

		foreach ($meta as $tableName => $tableMeta) {
			$tablesMeta[$tableName] = TableMeta::factory($tableMeta ?? []);
		}

		return new self($tablesMeta);
	}

	public function getTable(string $name): ?TableMeta
	{
		if ($this->offsetExists($name)) {
			return $this->offsetGet($name);
		}

		return null;
	}
}
