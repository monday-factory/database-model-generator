<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Storage\ALowLevelRelationalDatabaseStorage;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use ReflectionClass;

class LowLevelDatabaseStorageGenerator
{

	use TBaseMethods;

	public PhpFile $file;

	/** @param array<scalar, mixed> $definition */
	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;
		$this->file = $this->generate();
		$this->content = (string) $this->file;
	}

	private function generate(): PhpFile
	{
		$file = new PhpFile;
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
			->setType('string');


		$class->addProperty('idField', $this->definition['databaseTableId'])
			->setVisibility('protected')
			->setType('string');


		$class->addProperty('rowFactoryClass', new PhpLiteral($this->getRowFactoryClassName() . '::class'))
			->setVisibility('protected')
			->setType('string');


		$class->addProperty('collectionFactory', new PhpLiteral($this->getCollectionFactoryClassName() . '::class'))
			->setVisibility('protected')
			->setType('string');


		$rowFactoryClassReflection = new ReflectionClass(
			$this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName(),
		);

		$this->addFindOneMethod($class, $rowFactoryClassReflection);
		$this->addFindOneByCriteriaMethod($class, $rowFactoryClassReflection);

		$collectionFactoryClassReflection = new ReflectionClass(
			$this->getCollectionFactoryNamespace() . '\\' . $this->getCollectionFactoryClassName(),
		);

		$this->addFindMethod($class, $collectionFactoryClassReflection, $rowFactoryClassReflection);
		$this->addFindAllMethod($class, $collectionFactoryClassReflection, $rowFactoryClassReflection);
		$this->addFindByCriteriaMethod($class, $collectionFactoryClassReflection, $rowFactoryClassReflection);

		return $file;
	}

	private function addFindOneMethod(ClassType $class, ReflectionClass $rowFactoryClassReflection): void
	{
		$class->addMethod('findOne')
			->setReturnType($rowFactoryClassReflection->getName())
			->setReturnNullable()
			->addBody('$data = parent::findOne($id);')
			->addBody(
				'assert($data === null || $data instanceOf ?);',
				[new PhpLiteral($this->getRowFactoryClassName())],
			)
			->addBody('')
			->addBody('return $data;')
			->addComment('@var string|int $id')
			->addParameter('id');
	}

	private function addFindOneByCriteriaMethod(ClassType $class, ReflectionClass $rowFactoryClassReflection): void
	{
		$class->addMethod('findOneByCriteria')
			->setReturnType($rowFactoryClassReflection->getName())
			->setReturnNullable()
			->addBody('$data = parent::findOneByCriteria($criteria);')
			->addBody(
				'assert($data === null || $data instanceOf ?);',
				[new PhpLiteral($this->getRowFactoryClassName())],
			)
			->addBody('')
			->addBody('return $data;')
			->addComment('@param array <int|string, mixed> $criteria')
			->addParameter('criteria')
			->setType('array');
	}

	private function addFindMethod(
		ClassType $class,
		ReflectionClass $collectionFactoryClassReflection,
		ReflectionClass $rowFactoryClassReflection
	): void
	{
		$findMethod = $class->addMethod('find')
			->setReturnType($collectionFactoryClassReflection->getName())
			->addBody('$data = parent::find($ids, $limit, $offset);')
			->addBody('assert($data instanceOf ?);', [new PhpLiteral($this->getCollectionFactoryClassName())])
			->addBody('')
			->addBody('return $data;')
			->addComment('@param array<int|string, mixed> $ids')
			->addComment('')
			->addComment(
				'@return ' . $collectionFactoryClassReflection->getShortName()
				. '<int|string, ' . $rowFactoryClassReflection->getShortName() . '>',
			);

		$findMethod->addParameter('ids')->setType('array');
		$findMethod->addParameter('limit')->setType('int')->setNullable()->setDefaultValue(null);
		$findMethod->addParameter('offset')->setType('int')->setNullable()->setDefaultValue(null);
	}

	private function addFindAllMethod(ClassType $class, ReflectionClass $collectionFactoryClassReflection,
		ReflectionClass $rowFactoryClassReflection): void
	{
		$findMethod = $class->addMethod('findAll')
			->setReturnType($collectionFactoryClassReflection->getName())
			->addBody('$data = parent::findAll($limit, $offset);')
			->addBody('assert($data instanceOf ?);', [new PhpLiteral($this->getCollectionFactoryClassName())])
			->addBody('')
			->addBody('return $data;')
			->addComment(
				'@return ' . $collectionFactoryClassReflection->getShortName()
				. '<int|string, ' . $rowFactoryClassReflection->getShortName() . '>',
			);

		$findMethod->addParameter('limit')->setType('int')->setNullable()->setDefaultValue(null);
		$findMethod->addParameter('offset')->setType('int')->setNullable()->setDefaultValue(null);
	}

	private function addFindByCriteriaMethod(ClassType $class, ReflectionClass $collectionFactoryClassReflection,
		ReflectionClass $rowFactoryClassReflection): void
	{
		$findMethod = $class->addMethod('findByCriteria')
			->setReturnType($collectionFactoryClassReflection->getName())
			->addBody('$data = parent::findByCriteria($criteria, $limit, $offset);')
			->addBody('assert($data instanceOf ?);', [new PhpLiteral($this->getCollectionFactoryClassName())])
			->addBody('')
			->addBody('return $data;')
			->addComment('@param array<int|string, mixed> $criteria')
			->addComment('')
			->addComment(
				'@return ' . $collectionFactoryClassReflection->getShortName()
				. '<int|string, ' . $rowFactoryClassReflection->getShortName() . '>',
			);

		$findMethod->addParameter('criteria')->setType('array');
		$findMethod->addParameter('limit')->setType('int')->setNullable()->setDefaultValue(null);
		$findMethod->addParameter('offset')->setType('int')->setNullable()->setDefaultValue(null);
	}

}
