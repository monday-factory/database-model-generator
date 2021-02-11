<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use Nette\Utils\Strings;

trait TBaseMethods
{

	/** @var array<scalar, mixed> */
	private array $definition;

	private string $name;

	private string $content;

	private string $fileNamespace;

    public function getFileNamespace(): string
    {
        return $this->fileNamespace;
    }

    public function getContent(): string
    {
        return $this->content;
    }

	private function toCamelCase(string $string): string
	{
		$result = preg_replace('/[-\_]/', '', Strings::firstLower(Strings::capitalize($string)));

		return $result ?? '';
	}

	private function getClassName(): string
	{
		return ucfirst($this->name);
	}

	private function getNamespace(string $concreteNamespace): string
	{
		return $this->definition['namespace'] . '\\' . $concreteNamespace;
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
