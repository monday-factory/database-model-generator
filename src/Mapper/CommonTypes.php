<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Mapper;

class CommonTypes
{

	private array $knownTypes = [];

	public function __construct()
	{
		$this->knownTypes = [
			'\\Ramsey\\Uuid\\UuidInterface' => [$this, 'ramseyUuid'],
			'\\Ramsey\\Uuid\\Uuid' => [$this, 'ramseyUuid'],
		];
	}

	public function getMapper(string $className): ?MapperObjectInterface
	{
		if (strlen($className) === 0) {
			return null;
		}

		if (! array_key_exists($className, $this->knownTypes)) {

			return null;
		}

		return call_user_func($this->knownTypes[$className]);
	}

	private function ramseyUuid(): MapperObjectInterface
	{
		return new MethodTypesMapper('\\Ramsey\\Uuid\\Uuid' ,'\\Ramsey\\Uuid\\UuidInterface', 'fromString', 'toString');
	}

}
