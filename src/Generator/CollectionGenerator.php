<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Collection\BaseDatabaseDataCollection;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use ReflectionClass;

class CollectionGenerator
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

		$namespace = $file->addNamespace($this->getNamespace('Collection'));
		$namespace
			->addUse(BaseDatabaseDataCollection::class)
			->addUse($this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName());

		$class = $namespace->addClass($this->getCollectionFactoryClassName());
		$this->fileNamespace = $namespace->getName() . '\\' . $class->getName();

		$class->setExtends(BaseDatabaseDataCollection::class);

		if (isset($this->definition['databaseTableId']) && (string) $this->definition['databaseTableId'] !== '') {
			$idFieldSerializerProperty = $class->addProperty('idFieldSerializer')
				->setType('string')
				->setNullable();

			$idField = $this->findIdField();

			if (is_array($idField) && isset($idField['toString'])) {
				$idFieldSerializer = preg_replace("/(\(.*\))/m", '', $idField['toString']);
				$idFieldSerializer = preg_replace("/(->)+/m", '', $idFieldSerializer);

				$idFieldSerializerProperty->setValue($idFieldSerializer)->setProtected();
			}
		}

		$this->addCreateMethod($class);

		$rowFactoryClassReflection = new ReflectionClass(
			$this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName(),
		);

		$this->addGetByKeyMethod($class, $rowFactoryClassReflection);
		$this->addCurrentMethod($class, $rowFactoryClassReflection);

		return $file;
	}

	/** @return array<scalar, mixed>|null */
	private function findIdField(): ?array
	{
		return $this->definition['databaseCols']['rw'][$this->definition['databaseTableId']]
			   ?? $this->definition['databaseCols']['ro'][$this->definition['databaseTableId']]
				  ?? null;
	}

	private function addCreateMethod(ClassType $class): void
	{
		$method = $class->addMethod('create');
		$method->addComment('@param iterable<int|string, mixed> $data');

		$method->addBody(
			'return new self($data, ?, $idField);',
			[new PhpLiteral($this->getRowFactoryClassName() . '::class')],
		)
			->setVisibility('public')
			->setStatic()
			->setReturnType('self')
			->addParameter('data')
			->setType('iterable');

		$method->addParameter('idField')
			->setType('string')
			->setNullable()
			->setDefaultValue(null);
	}

    private function addGetByKeyMethod(ClassType $class, ReflectionClass $rowFactoryClassReflection): void
	{
		$method = $class->addMethod('getByKey');
		$method->addComment('@param int|string $key');

		$method->addBody('$data = parent::getByKey($key);')
			->addBody(
				'assert($data === null || $data instanceOf ?);',
				[new PhpLiteral($this->getRowFactoryClassName())],
			)
			->addBody('')
			->addBody('return $data;')
			->setVisibility('public')
			->setReturnNullable()
			->setReturnType($rowFactoryClassReflection->getName());

		$method->addParameter('key');
	}

    private function addCurrentMethod(ClassType $class, ReflectionClass $rowFactoryClassReflection): void
	{
		$method = $class->addMethod('current');
		$method->addBody('$data = parent::current();')
			->addBody(
				'assert($data === false || $data instanceOf ?);',
				[new PhpLiteral($this->getRowFactoryClassName())],
			)
			->addBody('')
			->addBody('return $data;')
			->setComment('@return false|' . $rowFactoryClassReflection->getShortName())
			->setVisibility('public');
	}

}
