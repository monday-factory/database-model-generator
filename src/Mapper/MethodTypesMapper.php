<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Mapper;

class MethodTypesMapper implements MapperObjectInterface
{

	protected \ReflectionClass $reflectionClass;

	public function __construct(private string $className, private ?string $interfaceName, private string $fromStringMethod, private string $toStringMethod) {
		if (! class_exists($this->className) and !interface_exists($this->className)) {
			throw new \InvalidArgumentException(sprintf('Class %s not found', $this->className));
		}

		$this->reflectionClass = new \ReflectionClass($this->className);
	}

	public function getToStringLiteral(): string
	{
		$method = $this->getMethodReflectionIfValid($this->toStringMethod);

		if ($method->isStatic()) {

			return $this->getClassName() . '::' . $method->getName() . '()';
		} else {

			return '->' . $method->getName() . '()';
		}
	}

	public function getFromStringLiteral($value): string
	{
		$method = $this->getMethodReflectionIfValid($this->fromStringMethod);

		if ($method->isStatic()) {

			return $this->getClassName() . '::' . $method->getName() . '(' . $value . ')';
		} else {

			return '->' . $method->getName() . '(\'' . $value . '\')';
		}
	}

	private function getMethodReflectionIfValid(string $methodName): \ReflectionMethod
	{
		if (! $this->reflectionClass->hasMethod($methodName)) {
			throw new \InvalidArgumentException(sprintf('Class %s doesn\'t have method %s.', $this->className, $methodName));
		}

		$method = $this->reflectionClass->getMethod($methodName);

		if (! $method->isPublic()) {
			throw new \InvalidArgumentException(sprintf('Invoked method %s of class %s is not public', $methodName, $this->className));
		}

		if ($method->getNumberOfRequiredParameters() > 1) {
			throw new \LogicException(sprintf('Method %s of class %s have more then one required parameters.', $methodName, $this->className));
		}

		return $method;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getInterfaceName(): string
	{
		return $this->interfaceName ?? $this->className;
	}

}
