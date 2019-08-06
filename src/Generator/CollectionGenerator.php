<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Colection\BaseDatabaseDataCollection;
use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;

class CollectionGenerator
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

		$namespace = $file->addNamespace($this->getNamespace('Collection'));
		$namespace
			->addUse(BaseDatabaseDataCollection::class)
			->addUse(IDatabaseDataCollection::class)
			->addUse($this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName());

		$class = $namespace->addClass($this->getCollectionFactoryClassName());
		$this->fileNamespace = $namespace->getName() . '\\' . $class->getName();

		$class->setExtends(BaseDatabaseDataCollection::class);

		$methodCreate = $class->addMethod('create');

		$methodCreate->addBody('return new static($data, ?, $idField);', [new PhpLiteral($this->getRowFactoryClassName() . '::class')])
			->setVisibility('public')
			->setStatic()
			->setReturnType(IDatabaseDataCollection::class)
			->addComment("@param array \$data\n")
			->addComment('@return ' . (new \ReflectionClass(IDatabaseDataCollection::class))->getShortName())
			->addParameter('data')
			->setTypeHint('iterable');

		$methodCreate->addParameter('idField')
			->setTypeHint('string')
			->setNullable()
			->setDefaultValue(null);

		return (string) $file;
	}

}
