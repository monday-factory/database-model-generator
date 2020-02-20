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

		if (isset($this->definition['databaseTableId']) && ! empty($this->definition['databaseTableId'])) {
			$idFieldSerializerProperty = $class->addProperty('idFieldSerializer');

			$idField = $this->findIdField();

			if (is_array($idField) && isset($idField['toString'])) {

				$idFieldSerializer = preg_replace("/(\(.*\))/m",'', $idField['toString']);
				$idFieldSerializer = preg_replace("/(->)+/m",'', $idFieldSerializer);

				$idFieldSerializerProperty->setValue($idFieldSerializer);
			}
		}

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

	private function findIdField(): ?array
	{
		if (
			isset($this->definition['databaseCols']['rw'])
			&& isset(
				$this->definition['databaseCols']['rw'][$this->definition['databaseTableId']]
			)
		) {
			return $this->definition['databaseCols']['rw'][$this->definition['databaseTableId']];
		} else if (isset($this->definition['databaseCols']['rw'])
			&& isset(
				$this->definition['databaseCols']['ro'][$this->definition['databaseTableId']]
			)
		) {
			return $this->definition['databaseCols']['ro'][$this->definition['databaseTableId']];
		}

		return null;
	}

}
