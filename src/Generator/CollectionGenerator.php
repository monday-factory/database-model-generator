<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Colection\BaseDatabaseDataCollection;
use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;

class CollectionGenerator
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

		$namespace->addUse(BaseDatabaseDataCollection::class)
			->addUse(IDatabaseDataCollection::class)
			->addUse($this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName());

		$class = $namespace->addClass($this->getCollectionFactoryClassName());

		$class->setExtends(BaseDatabaseDataCollection::class);

		$class->addMethod('create')
			->addBody('return new static($data, ?);', [new PhpLiteral($this->getRowFactoryClassName() . '::class')])
			->setVisibility('public')
			->setStatic()
			->setReturnType(IDatabaseDataCollection::class)
			->addComment("@param array \$data\n")
			->addComment('@return ' . new PhpLiteral(IDatabaseDataCollection::class));

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
