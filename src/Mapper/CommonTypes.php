<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Mapper;

class CommonTypes
{

	private array $knownTypes = [];

	public function __construct()
	{
		$this->knownTypes = [
			'Ramsey\\Uuid\\UuidInterface' => [$this, 'ramseyUuid'],
			'Ramsey\\Uuid\\Uuid' => [$this, 'ramseyUuid'],
			'Consistence\\Enum\\Enum' => [$this, 'consistenceEnum'],
		];
	}

	public function getMapper(string $className): ?MapperObjectInterface
	{
		if (strlen($className) === 0) {

			return null;
		}

		if (!class_exists($className) && !interface_exists($className)) {

			return null;
		}

		$classes = class_parents($className);
		array_unshift($classes, $className);

		foreach ($classes as $class) {
			if (array_key_exists($class, $this->knownTypes)) {

				return call_user_func($this->knownTypes[$class], $className);
			}
		}

		return null;
	}

	private function ramseyUuid(): MapperObjectInterface
	{
		return new MethodTypesMapper('Ramsey\\Uuid\\Uuid', 'Ramsey\\Uuid\\UuidInterface', 'fromString', 'toString');
	}

	private function consistenceEnum(string $className): MapperObjectInterface
	{
		return new MethodTypesMapper($className, null, 'get', 'getValue');
	}

}
