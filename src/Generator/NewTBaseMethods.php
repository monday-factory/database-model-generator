<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModelGenerator\Definition\Property;
use MondayFactory\DatabaseModelGenerator\Setting\NormalizeName;
use MondayFactory\DatabaseModelGenerator\Setting\Setting;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\Strings;

trait NewTBaseMethods
{

	/**
	 * @var array
	 */
	private $definition;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var PhpFile
	 */
	private $content;

	/**
	 * @var string
	 */
	private $fileNamespace;

	private function normalizeName(string $string): string
	{
		if (NormalizeName::camelCase === Setting::$normalizeName) {
			$result = $this->convertToCamelCase($string);
		} elseif (NormalizeName::snake_case === Setting::$normalizeName) {
			$result = $this->convertToSnakeCase($string);
		} elseif (NormalizeName::PascalCase === Setting::$normalizeName) {
			$result = $this->convertToPascalCase($string);
		} else {
			$result =  $string;
		}

		return !is_null($result)
			? $result
			: '';
	}

	private function convertToCamelCase(string $string): string
	{
		return Strings::firstLower(Strings::replace($string, '/_([a-z])/i', function ($matches) {
			return Strings::upper($matches[1]);
		}));
	}

	private function convertToPascalCase(string $string): string
	{
		return Strings::firstUpper($this->convertToCamelCase($string));
	}

	private function convertToSnakeCase(string $string): string
	{
		return strtolower(preg_replace(
			'/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '_', $string));
	}

	private function getToDatabaseArrayWrapper(Property $property): string
	{
		switch ($property->getPureType()) {
			case 'json':
				return ' json_encode($this->' . $this->convertToCamelCase($property->getName()) . ')';
			default:
				return ' $this->' . $this->convertToCamelCase($property->getName());
		}
	}

	public function getFileNamespace(): string
	{
		return $this->fileNamespace;
	}

	public function getContent(): string
	{
		return (string) $this->content;
	}

	private function getClassName(): string
	{
		return ucfirst($this->name);
	}

	private function getNamespace(string $concreteNamespace): string
	{
		return $this->classNamespace . '\\' . $concreteNamespace;
	}

	private function getRowFactoryNamespace(): string
	{
		return $this->getNamespace('Data');
	}

	private function getRowFactoryClassName(): string
	{
		return $this->getClassName() . 'Data';
	}

	private function getCollectionFactoryNamespace(): string
	{
		return $this->getNamespace('Collection');
	}

	private function getCollectionFactoryClassName(): string
	{
		return $this->getClassName() . 'Collection';
	}

	private function getDatabaseLowLevelStorageNamespace(): string
	{
		return $this->getNamespace('Storage');
	}

	private function getDatabaseLowLevelStorageClassName(): string
	{
		return $this->getClassName() . 'DatabaseLowLevelStorage';
	}

}
