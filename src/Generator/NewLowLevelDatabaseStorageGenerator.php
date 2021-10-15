<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Storage\ALowLevelRelationalDatabaseStorage;
use MondayFactory\DatabaseModelGenerator\Definition\Table;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;

class NewLowLevelDatabaseStorageGenerator
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

		$namespace = $file->addNamespace($this->getNamespace('Storage'));
		$namespace->addUse(ALowLevelRelationalDatabaseStorage::class)
			->addUse($this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName())
			->addUse($this->getCollectionFactoryNamespace() . '\\' . $this->getCollectionFactoryClassName());

		$class = $namespace->addClass($this->getDatabaseLowLevelStorageClassName());
		$this->fileNamespace = $namespace->getName() . '\\' . $class->getName();

		$class->setExtends(ALowLevelRelationalDatabaseStorage::class);

		$class->addProperty('tableName', $this->tableDefinition->getName())
			->setType('string')
			->setVisibility('protected');


		$class->addProperty('idField', $this->tableDefinition->getTablePrimaryName())
			->setType('string')
			->setVisibility('protected');


		$class->addProperty('rowFactoryClass', new PhpLiteral($this->getRowFactoryClassName() . '::class'))
			->setType('string')
			->setVisibility('protected');


		$class->addProperty('collectionFactory',  new PhpLiteral($this->getCollectionFactoryClassName() . '::class'))
			->setType('string')
			->setVisibility('protected');

		return $file;
	}

}
