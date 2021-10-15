<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Colection\BaseDatabaseDataCollection;
use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use MondayFactory\DatabaseModelGenerator\Definition\Table;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;

class NewCollectionGenerator
{

	use NewTBaseMethods;

	/**
	 * @var PhpFile
	 */
	public $file;

	public function __construct(private Table $tableDefinition, private string $classNamespace)
	{
		$this->definition = null;
		$this->name = $this->toCamelCase($this->tableDefinition->getName());
		$this->file = $this->generate();
		$this->content = (string) $this->file;
	}

	private function generate(): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		$namespace = $file->addNamespace($this->getNamespace('Collection'));
		$namespace
			->addUse(BaseDatabaseDataCollection::class)
			->addUse(IDatabaseDataCollection::class)
			->addUse($this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName());

		$class = $namespace->addClass($this->getCollectionFactoryClassName());
		$this->fileNamespace = $namespace->getName() . '\\' . $class->getName();

		$class->setExtends(BaseDatabaseDataCollection::class);

		if ($this->tableDefinition->getTablePrimary()->isClass()) {
			$idFieldSerializerProperty = $class->addProperty('idFieldSerializer')->setType('string');
			$idFieldSerializer = $this->tableDefinition->getTablePrimary()->getMapper()->getToStringLiteral();

			$idFieldSerializerProperty->setValue($idFieldSerializer)->setProtected();
		}

		$methodCreate = $class->addMethod('create');

		$methodCreate->addBody('return new static($data, ?, $idField);', [new PhpLiteral($this->getRowFactoryClassName() . '::class')])
			->setVisibility('public')
			->setStatic()
			->setReturnType(IDatabaseDataCollection::class)
			->addParameter('data')
			->setTypeHint('iterable');

		$methodCreate->addParameter('idField')
			->setTypeHint('string')
			->setNullable()
			->setDefaultValue(null);

		return $file;
	}

}
