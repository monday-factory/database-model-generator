<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Data\IDatabaseData;
use MondayFactory\DatabaseModelGenerator\Definition\Property;
use MondayFactory\DatabaseModelGenerator\Definition\Table;
use MondayFactory\DatabaseModelGenerator\Mapper\MapperObjectInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;

class NewDataGenerator
{

	use NewTBaseMethods;

	private PhpNamespace $namespace;

	private ClassType $class;

	public PhpFile $file;

	public function __construct(private Table $tableDefinition, private string $classNamespace)
	{
		$this->definition = null;
		$this->name = $this->toCamelCase($this->tableDefinition->getName());
		$this->content = $this->generate();
	}

	private function generate(): PhpFile
	{
		$this->file = new PhpFile;
		$this->file->setStrictTypes();

		$this->namespace = $this->file->addNamespace($this->getNamespace("Data"));
		$this->namespace->addUse(IDatabaseData::class);
		$this->class = $this->namespace->addClass($this->getRowFactoryClassName());
		$this->fileNamespace = $this->namespace->getName() . '\\' . $this->class->getName();

		$this->class->setImplements([IDatabaseData::class]);

		$this->addConstructor();

		$this->addFromDataMethod();
		$this->addFromRowMethod();
		$this->addToArrayMethod();
		$this->addToDatabaseArrayMethod();

		/**
		 * @var Property $property
		 */
		foreach ($this->tableDefinition->getReadWriteProperties() as $property) {
			$this->addProperty($property->getName(), $property);

			$this->addGetter($property->getName(), $property);
		}

		/**
		 * @var Property $property
		 */
		foreach ($this->tableDefinition->getReadOnlyProperties() as $property) {
			$this->addProperty($property->getName(), $property);
			$this->addSetter($property->getName(), $property);
			$this->addGetter($property->getName(), $property);
		}

		return $this->file;
	}

	private function addConstructor(): void
	{
		$constructor = $this->class->addMethod('__construct')
			->setVisibility('public');

		/**
		 * @var Property $property
		 */
			foreach ($this->tableDefinition->getReadWriteProperties() as $property) {
				if ($property->isClass()) {
					$this->namespace->addUse($property->getType(true));
				}

				$param = $constructor->addParameter($this->toCamelCase($property->getName()))
					->setType($property->getType(true))
					->setNullable($property->isNullable());

				if ($property->getDefaultValue() && ! $property->getMapper() instanceof MapperObjectInterface) {
					$param->setDefaultValue($property->getDefaultValue());
				}

				$constructor->addBody('$this->? = ?;', [
					$this->toCamelCase($property->getName()),
					new PhpLiteral('$' . $this->toCamelCase($property->getName())),
				]);

			}
	}

	private function addFromDataMethod(): void
	{
		$fromData = $this->class->addMethod('fromData')
			->setStatic()
			->setReturnType('self');

		$fromData->addParameter('data')
			->setType('iterable');

		$rwProperties = $this->tableDefinition->getReadWriteProperties()->toArray();
		$selfBody = '';

		/**
		 * @var Property $property
		 */
		foreach ($rwProperties as $property) {
			$selfBody .= "\t\$data['" . $this->toCamelCase((string) $property->getName()) . '\']';

			next($rwProperties) === false ?: $selfBody .= ",\n";
		}

		$fromData->addBody("return new self(\n\t?\n);", [new PhpLiteral($selfBody)]);
	}

	private function addFromRowMethod(): void
	{
		$fromRow = $this->class->addMethod('fromRow')
			->setStatic()
			->setReturnType('self')
			->addComment('@todo Finish implementation.');

		$fromRow->addParameter('row')
			->setType('array');

		if ($this->tableDefinition->getReadOnlyProperties()->count() > 0) {
			$fromRow->addBody('$instance = new self(');
		} else {
			$fromRow->addBody("return new self(");
		}

		$rwProperties = $this->tableDefinition->getReadWriteProperties()->toArray();

		/**
		 * @var Property $property
		 */
		foreach ($rwProperties as $property) {
			$delimiter = next($rwProperties) === false
				? ""
				: ",";

			$fromRowNullablePrefix = '';
			$fromRowTypecastingPrefix = '';

				if ($property->isClass()) {
					$usedAlias = null;
					$this->namespace->addUse($property->getMapper()->getClassName(), null, $usedAlias);

					$fromRowBody = $property->getMapper()->getFromStringLiteral('$row[\'' . $property->getName() . '\']') . $delimiter;
				} else {
					$fromRowBody = "\$row['" . $property->getName() . '\']' . $delimiter;
				}

				if (in_array($property->getType(), [
					'bool',
					'int',
					'double',
					'string',
				])) {
					$fromRowTypecastingPrefix = "(" . $property->getType() . ") ";
				}

				if ($property->isNullable()) {
					$fromRowNullablePrefix = 'is_null($row[\'' . $property->getName() . '\']) ? null : ';
				}

				$fromRow->addBody("\t" . $fromRowNullablePrefix . $fromRowTypecastingPrefix . $fromRowBody);
		}

		if ($this->tableDefinition->getReadOnlyProperties()->count() > 0) {
			$fromRow->addBody(');');
			$roProperties = $this->tableDefinition->getReadOnlyProperties()->toArray();

			foreach ($roProperties as $name => $property) {
				$delimiter = ';';

				$pastedProperty = $property->getMapper() instanceof MapperObjectInterface
					? $property->getMapper()->getFromStringLiteral('$row[\'' . $property->getName() . '\']')
					: '$row[\'' . $property->getName() . '\']';

				if ($property->isNullable()) {
					$pastedProperty .= ' ?? null';
				}

				$fromRow->addBody('$instance->set' . ucfirst($this->toCamelCase($property->getName())) . "({$pastedProperty})" . $delimiter);
			}

			$fromRow->addBody("\n" . 'return $instance;');
		} else {
			$fromRow->addBody(');');
		}
	}

	private function addToArrayMethod(): void
	{
		$this->class->addMethod('toArray')
			->setReturnType('array')
			->setBody('return get_object_vars($this);');
	}

	private function addToDatabaseArrayMethod(): void
	{
		$toArray = $this->class->addMethod('toDatabaseArray')
			->setReturnType('array')
			->addComment('@todo Finish implementation.');

		$body = "return [\n";


		/**
		 * @var Property $property
		 */
			foreach ($this->tableDefinition->getReadWriteProperties() as $property) {

				$toString = $property->getMapper() instanceof MapperObjectInterface
					? $property->getMapper()->getToStringLiteral()
					: '';

				$body .= "\t" . '\'' . $property->getName() . '\' =>';

				if ($property->isNullable()) {
					$body .= ' is_null($this->' . $this->toCamelCase((string) $property->getName()) . ') ? null :';
				}

				$body .= ' $this->' . $this->toCamelCase((string) $property->getName())  . $toString .  ",\n";
			}

		$body .= '];';

		$toArray->setBody($body);
	}

	private function addGetter(string $name, Property $propertyDefinition): void
	{
		$this->class->addMethod('get' . ucfirst($this->toCamelCase($propertyDefinition->getName())))
			->setReturnType($propertyDefinition->getType(true))
			->setReturnNullable($propertyDefinition->isNullable())
			->addBody('return $this->?;', [$this->toCamelCase($propertyDefinition->getName())]);
	}

	private function addSetter(string $name, Property $propertyDefinition): void
	{
		$setter = $this->class->addMethod('set' . ucfirst($this->toCamelCase($name)))
			->setVisibility('private')
			->addBody("\$this->? = $?;\n", [$this->toCamelCase($propertyDefinition->getName()), $this->toCamelCase($propertyDefinition->getName())])
			->addBody('return $this;')
			->setReturnType('self');

		$setter->addParameter($this->toCamelCase($name))
			->setNullable($propertyDefinition->isNullable())
			->setType($propertyDefinition->getType(true));
	}

	private function addProperty(string $name, Property $propertyDefinition): void
	{
		if ($propertyDefinition->isClass()) {
			$this->namespace->addUse($propertyDefinition->getType(true));
		}

		$this->class->addProperty($this->toCamelCase($propertyDefinition->getName()))
			->setVisibility('private')
			->setNullable($propertyDefinition->isNullable())
			->setType($propertyDefinition->getType(true));
	}

}
