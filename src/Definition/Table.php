<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use Ramsey\Collection\CollectionInterface;

class Table
{

	public function __construct(
		private string $name,
		private PropertyCollection $properties,
		private ?string $tablePrimary,
	)
	{}

	public function getReadOnlyProperties(): CollectionInterface
	{
		return $this->properties->where('isReadOnly', true);
	}

	public function getReadWriteProperties(): CollectionInterface
	{
		return $this->properties->where('isReadOnly', false);
	}

	public function getProperties(): CollectionInterface
	{
		return $this->properties;
	}

	public function getProperty(string $name): Property
	{
		return $this->properties->where('getName', $name)->first();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getTablePrimaryName(): ?string
	{
		return $this->tablePrimary;
	}

	public function getTablePrimary(): Property
	{
		return $this->properties->where('getName', $this->tablePrimary)->first();
	}

}
