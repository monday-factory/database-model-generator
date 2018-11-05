<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Storage\ALowLevelRelationalDatabaseStorage;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;

class LowLevelDatabaseStorageGenerator
{

	use TBaseMethods;

	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;
		$this->content = $this->generate();
	}

	private function generate(): string
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

		$class->addProperty('tableName', $this->definition['databaseTable'])
			->setVisibility('protected')
			->addComment("\n@var string");


		$class->addProperty('idField', $this->definition['databaseTableId'])
			->setVisibility('protected')
			->addComment("\n@var string|int");


		$class->addProperty('rowFactoryClass', new PhpLiteral($this->getRowFactoryClassName() . '::class'))
			->setVisibility('protected')
			->addComment("\n@var string");


		$class->addProperty('collectionFactory',  new PhpLiteral($this->getCollectionFactoryClassName() . '::class'))
			->setVisibility('protected')
			->addComment("\n@var string");

		return (string) $file;
	}

}
