<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use MondayFactory\DatabaseModelGenerator\Mapper\MapperObjectInterface;

class Property
{

	private $isClass = false;

	public function __construct(
		private string $name,
		private $type,
		private bool $isNullable = false,
		private $defaultValue = null,
		private int $maxLength = 0,
		private bool $readOnly = false,
		private ?MapperObjectInterface $mapper = null,
	)
	{
		if ($this->mapper instanceof MapperObjectInterface) {
			$this->isClass = true;
		}
	}

	public function isClass(): bool
	{
		return $this->isClass;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(bool $returnInterfaceIfExists = false): string
	{
		if ($this->mapper instanceof MapperObjectInterface) {

			return $returnInterfaceIfExists ? $this->mapper->getInterfaceName() : $this->mapper->getClassName();
		}

		return $this->type;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

	public function getDefaultValue(): ?string
	{
		return $this->defaultValue;
	}

	public function getMaxLength(): int
	{
		return $this->maxLength;
	}

	public function isReadOnly(): bool
	{
		return $this->readOnly;
	}

	public function getMapper(): ?MapperObjectInterface
	{
		return $this->mapper;
	}

}
