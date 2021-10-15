<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class Definition
{

	public function __construct(private TableCollection $tableCollection)
	{}

	public function getTable(string $name): Table
	{
		return $this->tableCollection->where('getName', $name)->first();
	}

	public function getTables(): TableCollection
	{
		return $this->tableCollection;
	}

}
