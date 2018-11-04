<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use Nette\PhpGenerator\PhpNamespace;

class LowLevelDatabaseStorageGenerator
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
	 * @param array $definition
	 * @param string $neonName
	 */
	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;

	}

	public function generate()
	{
		$namespace = new PhpNamespace($this->getNamespace('Storage'));
		$namespace->addUse(ALowLevelRelationalDatabaseStorage::class);
		$class = $namespace->addClass($this->getDatabaseLowLevelStorageClassName());

		$class->setExtends(ALowLevelRelationalDatabaseStorage::class);

		$class->addProperty('tableName', $this->definition['databaseTableId'])
			->setVisibility('protected')
			->addComment("\n@var string");


		$class->addProperty('idField', $this->definition['databaseTable'])
			->setVisibility('protected')
			->addComment("\n@var string|int");


		$class->addProperty('rowFactoryClass', $this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName())
			->setVisibility('protected')
			->addComment("\n@var string");


		$class->addProperty('collectionFactory',  $this->getCollectionFactoryNamespace() . '\\' . $this->getCollectionFactoryClassName())
			->setVisibility('protected')
			->addComment("\n@var string");

		return (string) $namespace;
	}

	private function getClassName()
	{
		return ucfirst($this->name);
	}

	private function getNamespace(string $concreteNamespace)
	{
		return $this->definition['namespace'] . '\\' . $concreteNamespace;
	}

	private function getRowFactoryNamespace()
	{
		return $this->getNamespace('Data');
	}

	private function getRowFactoryClassName()
	{
		return $this->getClassName() . 'Data';
	}

	private function getCollectionFactoryNamespace()
	{
		return $this->getNamespace('Collection');
	}

	private function getCollectionFactoryClassName()
	{
		return $this->getClassName() . 'Collection';
	}

	private function getDatabaseLowLevelStorageNamespace()
	{
		return $this->getNamespace('Storage');
	}

	private function getDatabaseLowLevelStorageClassName()
	{
		return $this->getClassName() . 'DatabaseLowLevelStorage';
	}

}
