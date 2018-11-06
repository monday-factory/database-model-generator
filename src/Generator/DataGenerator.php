<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;

class DataGenerator
{

	use TBaseMethods;

	/**
	 * @var PhpNamespace
	 */
	private $namespace;

	/**
	 * @var ClassType
	 */
	private $class;

	/**
	 * @var PhpFile
	 */
	private $file;

	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;
		$this->content = $this->generate();
	}

	private function generate(): string
	{
		$this->file = new PhpFile;
		$this->file->setStrictTypes();

		$this->namespace = $this->file->addNamespace($this->getNamespace("Data"));
		$this->namespace->addUse(IDatabaseData::class);
		$this->class = $this->namespace->addClass($this->getRowFactoryClassName());
		$this->fileNamespace = $this->namespace->getName() . '\\' . $this->class->getName();

		$this->class->setImplements([IDatabaseData::class]);

		$constructor = $this->class->addMethod('__construct')
			->setVisibility('public');

		$this->addFromDataMethod();
		$this->addFromRowMethod();
		$this->addToArrayMethod();
		$this->addToDatabaseArrayMethod();

		foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
			$this->addProperty($name, $property);

			$constructor->addParameter($this->toCamelCase($name))
				->setTypeHint($property['type']);
			$constructor->addComment('@var $' . $this->toCamelCase($name));

			$constructor->addBody('$this->? = ?;', [$this->toCamelCase($name), new PhpLiteral('$' . $this->toCamelCase($name))]);

			$this->addGetter($name, $property);
		}

		foreach ($this->definition['databaseCols']['ro'] as $name => $property) {
			$this->addProperty($name, $property);
			$this->addSetter($name, $property);
			$this->addGetter($name, $property);
		}

		foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
			$this->addGetter($name, $property);
		}

		return (string) $this->file;
	}

	private function addFromDataMethod(): void
	{
		$fromData = $this->class->addMethod('fromData')
			->setStatic()
			->setReturnType(IDatabaseData::class)
			->addComment('@var iterable $data')
			->addComment("\n@return " . (new \ReflectionClass(IDatabaseData::class))->getShortName());

		$fromData->addParameter('data')
			->setTypeHint('iterable');

		$rwProperties = $this->definition['databaseCols']['rw'];
		$selfBody = '';

		foreach (array_keys($rwProperties) as $name) {

			dump($name);

			$selfBody .= "\t\$data['" . $this->toCamelCase((string) $name) . '\']';

			next($rwProperties) === false ?: $selfBody .= ",\n";
		}

		$fromData->addBody("return new self(\n?\n);", [new PhpLiteral($selfBody)]);
	}

	private function addFromRowMethod(): void
	{
		$fromRow = $this->class->addMethod('fromRow')
			->setStatic()
			->setReturnType(IDatabaseData::class)
			->addComment('@todo Finish implementation.')
			->addComment("\n@var array \$row")
			->addComment("\n@return " . (new \ReflectionClass(IDatabaseData::class))->getShortName());

		$fromRow->addParameter('row')
			->setTypeHint('array');

		$fromRow->addBody("return (new self(");

		$rwProperties = $this->definition['databaseCols']['rw'];

		foreach ($rwProperties as $name => $property) {
			$delimiter = next($rwProperties) === false
				? ""
				: ",";

			if (isset($property['fromString'])) {
				$fromRow->addBody(
					"\t\t"
					. str_replace('?', '$row[\'' . $name . '\']', $this->prepareFromStringArgument($property['fromString']))
					. $delimiter
				);
			} else {
				$fromRow->addBody("\t\t\$row['" . $name . '\']' . $delimiter);
			}
		}

		$fromRow->addBody("\t)\n)");
		$roProperties = $this->definition['databaseCols']['ro'];

		foreach ($roProperties as $name => $property) {
			$delimiter = next($roProperties) !== false
				? ""
				: ";";

			$pastedProperty = isset($property['fromString'])
				? str_replace('?', '$row[\'' . $name . '\']', $this->prepareFromStringArgument($property['fromString']))
				: '$row[\'' . $name . '\']';

			$fromRow->addBody("->set" . ucfirst($this->toCamelCase((string) $name)) . "({$pastedProperty})" . $delimiter);
		}
	}

	private function prepareFromStringArgument(string $parameter): string
	{
		$expandedFromString = explode('::', $parameter);

		if ($expandedFromString != false && count($expandedFromString) === 2) {
			$this->namespace->addUse($expandedFromString[0]);
			$classReflection = new \ReflectionClass($expandedFromString[0]);

			return $classReflection->getShortName() . '::' . $expandedFromString[1];
		}

		if (substr($parameter, 0, 1) === '\\') {
			$className = str_replace('(?)', '', $parameter);

			/**
			 * Check if the class exists
			 */
			new \ReflectionClass($className);

			return 'new ' . $parameter;
		}
	}

	private function addToArrayMethod(): void
	{
		$this->class->addMethod('toArray')
			->setReturnType('array')
			->setBody('return get_object_vars($this);')
			->addComment('@return array');
	}

	private function addToDatabaseArrayMethod(): void
	{
		$toArray = $this->class->addMethod('toDatabaseArray')
			->setReturnType('array')
			->addComment('@todo Finish implementation.')
			->addComment("\n@return array");

		$body = "return [\n";

		$rwProperties = $this->definition['databaseCols']['rw'];

		foreach ($rwProperties as $name => $property) {
			$toString = isset($property['toString'])
				? $this->prepareToStringArgument($property['toString'])
				: '';

			$body .= "\t" . '\'' . $name . '\' => $this->' . $this->toCamelCase((string) $name)  . $toString .  ",\n";
		}

		$body .= '];';

		$toArray->setBody($body);
	}

	private function prepareToStringArgument(string $argument): string
	{
		return '->' . str_replace('->', '', $argument);
	}

	private function addGetter(string $name, array $propertyDefinition): void
	{
		$getter = $this->class->addMethod('get' . ucfirst($this->toCamelCase($name)))
			->setReturnType($propertyDefinition['type'])
			->addBody('return $this->?;', [$this->toCamelCase($name)]);

		$getter->addComment('@return ' . $this->getTypehint($propertyDefinition['type']));
	}

	private function addSetter(string $name, array $propertyDefinition): void
	{
		$setter = $this->class->addMethod('set' . ucfirst($this->toCamelCase($name)))
			->addBody("\$this->? = $?;\n", [$this->toCamelCase($name), $this->toCamelCase($name)])
			->addBody('return $this;');

		$setter->addParameter($this->toCamelCase($name))
			->setTypeHint($propertyDefinition['type']);

		$setter->addComment('@var ' . $this->getTypehint($propertyDefinition['type']));
	}

	private function addProperty(string $name, array $propertyDefinition): void
	{
		$this->class->addProperty($this->toCamelCase($name))
			->setVisibility('private')
			->addComment("\n@var " . $this->getTypehint($propertyDefinition['type']));
	}

	private function getTypehint(string $type): string
	{
		if (substr_count($type, '\\') > 0) {
			$classReflection = new \ReflectionClass($type);
			$this->namespace->addUse($classReflection->getName());

			return $classReflection->getShortName();
		}

		return $type;
	}

}