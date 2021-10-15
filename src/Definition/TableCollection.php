<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use Ramsey\Collection\AbstractCollection;

class TableCollection extends AbstractCollection
{

	private string $collectionType;

	public function __construct(array $data)
	{
		$this->collectionType = Table::class;

		parent::__construct($data);
	}

	public function getType(): string
	{
		return $this->collectionType;
	}

	public function getTable(string $name): Table
	{
		return $this->where('getName', $name)->first();
	}

}
