<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;

class DataGenerator
{

	use TBaseMethods;

	public PhpFile $file;
	private PhpNamespace $namespace;
	private ClassType $class;

	/** @param array<scalar, mixed> $definition */
	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;
		$this->content = (string) $this->generate();
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

		if (isset($this->definition['databaseCols']['rw'])) {
			foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
				$this->addProperty($name, $property);

				$this->addGetter($name, $property);
			}
		}

		if (isset($this->definition['databaseCols']['ro'])) {
			foreach ($this->definition['databaseCols']['ro'] as $name => $property) {
				$this->addProperty($name, $property);
				$this->addSetter($name, $property);
				$this->addGetter($name, $property);
			}
		}

		if (isset($this->definition['databaseCols']['rw'])) {
			foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
				$this->addGetter($name, $property);
			}
		}

		return $this->file;
	}

	private function addConstructor(): void
	{
		$constructor = $this->class->addMethod('__construct')
			->setVisibility('public');

		if (isset($this->definition['databaseCols']['rw'])) {
			foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
				$nullable = $this->isPropertyNullable($property);

				$param = $constructor->addParameter($this->toCamelCase($name))
					->setType($property['type'])
					->setNullable($nullable);

				$this->addUse($property['type']);

				if (isset($property['default'])) {
					$param->setDefaultValue($property['default']);
				}

				$constructor->addBody('$this->? = ?;', [
					$this->toCamelCase($name),
					new PhpLiteral('$' . $this->toCamelCase($name)),
				]);
			}
		}
	}

	/** @param array<scalar, mixed> $property */
	private function isPropertyNullable(array $property): bool
	{
		return isset($property['nullable']) && (bool) $property['nullable'] === true;
	}

	private function addFromDataMethod(): void
	{
		$fromData = $this->class->addMethod('fromData')
			->setStatic()
			->setComment('@param array<int|string, mixed> $data')
			->setReturnType('self');

		$fromData->addParameter('data')
			->setType('array');

		$rwProperties = $this->definition['databaseCols']['rw'] ?? [];
		$selfBody = '';

		foreach (array_keys($rwProperties) as $name) {
			$selfBody .= "\t\$data['" . $this->toCamelCase((string) $name) . '\']';

			if ((bool) next($rwProperties) !== false) {
				$selfBody .= ",\n";
			}
		}

		$fromData->addBody("return new self(\n\t?\n);", [new PhpLiteral($selfBody)]);
	}

	private function addFromRowMethod(): void
	{
		$fromRow = $this->class->addMethod('fromRow')
			->setStatic()
			->setComment('@param array<int|string, mixed> $row')
			->setReturnType('self');

		$fromRow->addParameter('row')
			->setType('array');

		if (isset($this->definition['databaseCols']['ro']) && count($this->definition['databaseCols']['ro']) > 0) {
			$fromRow->addBody('$instance = new self(');
		} else {
			$fromRow->addBody("return new self(");
		}

		$rwProperties = $this->definition['databaseCols']['rw'] ?? [];

		foreach ($rwProperties as $name => $property) {
			$delimiter = next($rwProperties) === false
				? ""
				: ",";

			$fromRowNullablePrefix = '';
			$fromRowTypecastingPrefix = '';

			if (isset($property['fromString'])) {
				$classTest = [];
				preg_match(
					'/(?<construct>new +)?(?<class>[\\\\0-9a-zA-Z]+)(?<method>[::a-zA-Z0-9()?]+)/m',
					$property['fromString'],
					$classTest,
				);

				if (isset($classTest['class']) && class_exists($classTest['class'])) {
					$usedAlias = null;
					$this->namespace->addUse($classTest['class'], null, $usedAlias);

					$fromRowBody =
						($classTest['construct'] ? 'new ' : '') .
						$usedAlias .
						str_replace('?', '$row[\'' . $name . '\']', $classTest['method']) .
						$delimiter;
				} else {
					$fromRowBody =
						str_replace(
							'?',
							'$row[\'' . $name . '\']',
							$this->prepareFromStringArgument($property['fromString']),
						)
						. $delimiter;
				}
			} else {
				$fromRowBody = "\$row['" . $name . '\']' . $delimiter;
			}

			if (in_array($property['type'], ['bool', 'int', 'double', 'string'], true)) {
				$fromRowTypecastingPrefix = "(" . $property['type'] . ") ";
			}

			if ($this->isPropertyNullable($property)) {
				$fromRowNullablePrefix = 'is_null($row[\'' . $name . '\']) ? null : ';
			}

			$fromRow->addBody("\t" . $fromRowNullablePrefix . $fromRowTypecastingPrefix . $fromRowBody);
		}

		if (isset($this->definition['databaseCols']['ro']) && count($this->definition['databaseCols']['ro']) > 0) {
			$fromRow->addBody(");");
			$roProperties = $this->definition['databaseCols']['ro'];

			foreach ($roProperties as $name => $property) {
				$delimiter = ';';

				$pastedProperty = isset($property['fromString'])
					? str_replace(
						'?',
						'$row[\'' . $name . '\']',
						$this->prepareFromStringArgument($property['fromString']),
					)
					: '$row[\'' . $name . '\']';

				if (isset($property['nullable']) && boolval($property['nullable'])) {
					$pastedProperty .= ' ?? null';
				}

				$fromRow->addBody(
					'$instance->set' . ucfirst($this->toCamelCase((string) $name)) . "({$pastedProperty})" . $delimiter,
				);
			}

			$fromRow->addBody("\n" . 'return $instance;');
		} else {
			$fromRow->addBody(");");
		}
	}

	private function prepareFromStringArgument(string $parameter): string
	{
		$expandedFromString = explode('::', $parameter);

		if (count($expandedFromString) === 2) {
			$this->namespace->addUse($expandedFromString[0]);
			$classReflection = new ReflectionClass($expandedFromString[0]);

			return $classReflection->getShortName() . '::' . $expandedFromString[1];
		}

		if (substr($parameter, 0, 1) === '\\') {
			$className = str_replace('(?)', '', $parameter);

			/**
			 * Check if the class exists
			 */
			new ReflectionClass($className);

			return 'new ' . $parameter;
		}

		return $parameter;
	}

	private function addToArrayMethod(): void
	{
		$this->class->addMethod('toArray')
			->setReturnType('array')
			->setComment('@return array<int|string, mixed>')
			->setBody('return get_object_vars($this);');
	}

	private function addToDatabaseArrayMethod(): void
	{
		$toArray = $this->class->addMethod('toDatabaseArray')
			->setComment('@return array<int|string, mixed>')
			->setReturnType('array');

		$body = "return [\n";

		if (isset($this->definition['databaseCols']['rw'])) {
			$rwProperties = $this->definition['databaseCols']['rw'];

			foreach ($rwProperties as $name => $property) {
				$toString = isset($property['toString'])
					? $this->prepareToStringArgument($property['toString'])
					: '';

				$body .= "\t" . '\'' . $name . '\' =>';

				if ($this->isPropertyNullable($property)) {
					$body .= ' is_null($this->' . $this->toCamelCase((string) $name) . ') ? null :';
				}

				$body .= ' $this->' . $this->toCamelCase((string) $name) . $toString . ",\n";
			}
		}

		$body .= '];';

		$toArray->setBody($body);
	}

	private function prepareToStringArgument(string $argument): string
	{
		return '->' . str_replace('->', '', $argument);
	}

	/** @param array<scalar, mixed> $propertyDefinition */
	private function addGetter(string $name, array $propertyDefinition): void
	{
		$this->class->addMethod('get' . ucfirst($this->toCamelCase($name)))
			->setReturnType($propertyDefinition['type'])
			->setReturnNullable($this->isPropertyNullable($propertyDefinition))
			->addBody('return $this->?;', [$this->toCamelCase($name)]);

		$this->addUse($propertyDefinition['type']);
	}

	/** @param array<scalar, mixed> $propertyDefinition */
	private function addSetter(string $name, array $propertyDefinition): void
	{
		$setter = $this->class->addMethod('set' . ucfirst($this->toCamelCase($name)))
			->setVisibility('private')
			->addBody("\$this->? = $?;\n", [$this->toCamelCase($name), $this->toCamelCase($name)])
			->addBody('return $this;')
			->setReturnType('self');

		$setter->addParameter($this->toCamelCase($name))
			->setNullable($this->isPropertyNullable($propertyDefinition))
			->setType($propertyDefinition['type']);

		$this->addUse($propertyDefinition['type']);
	}

	/** @param array<scalar, mixed> $propertyDefinition */
	private function addProperty(string $name, array $propertyDefinition): void
	{
		$this->class->addProperty($this->toCamelCase($name))
			->setVisibility('private')
			->setType($propertyDefinition['type'])
			->setNullable($this->isPropertyNullable($propertyDefinition));

		$this->addUse($propertyDefinition['type']);
	}

	private function addUse(string $type): void
	{
		if (substr_count($type, '\\') > 0) {
			$classReflection = new ReflectionClass($type);
			$this->namespace->addUse($classReflection->getName());
		}
	}

}
