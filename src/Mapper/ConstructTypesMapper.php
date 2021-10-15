<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Mapper;

class ConstructTypesMapper extends MethodTypesMapper
{

	public function __construct(private string $className, private string $fromStringMethod, private string $toStringMethod) {
		parent::__construct($this->className, $this->fromStringMethod, $this->toStringMethod);
	}

	public function getFromStringLiteral($value): string
	{
		return 'new ' . $this->getClassName() . '(\'' . $value . '\')';
	}

}
