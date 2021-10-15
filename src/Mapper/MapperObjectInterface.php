<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Mapper;

interface MapperObjectInterface
{
	public function getToStringLiteral(): string;

	public function getFromStringLiteral($value): string;

	public function getInterfaceName(): string;

	public function getClassName(): string;
}