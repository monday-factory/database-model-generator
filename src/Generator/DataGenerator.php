<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Factory;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use Ramsey\Uuid\UuidInterface;

class DataGenerator
{

	/**
	 * @var array
	 */
	private $definition;

	/**
	 * @var string
	 */
	private $name;

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

	/**
	 * @param array $definition
	 * @param string $neonName
	 */
	public function __construct(array $definition, string $name)
	{
		$this->definition = $definition;
		$this->name = $name;

	}

	public function generate()
	{
		$this->file = new PhpFile;
		$this->file->setStrictTypes();

		$this->namespace = $this->file->addNamespace("Data");
		$this->namespace->addUse(IDatabaseData::class);
		$this->class = $this->namespace->addClass($this->getDatabaseLowLevelStorageClassName());

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
			$constructor->addBody('$this->? = ?;', [$this->toCamelCase($name), new PhpLiteral('$' . $this->toCamelCase($name))]);

			$this->addGetter($name, $property);
		}

		foreach ($this->definition['databaseCols']['ro'] as $name => $property) {
			$this->addProperty($name, $property);
			$this->addGetter($name, $property);
		}

		foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
			$this->addSetter($name, $property);
		}

			dump($this->namespace->getUses());

		return (string) $this->file;
	}

	private function addFromDataMethod()
	{
		$fromData = $this->class->addMethod('fromData')
			->setStatic()
			->setReturnType(IDatabaseData::class);

		$fromData->addParameter('data')
			->setTypeHint('iterable');

		$rwProperties = $this->definition['databaseCols']['rw'];
		$selfBody = '';

		foreach ($rwProperties as $name => $property) {

			$selfBody .= "\t\$data['" . $this->toCamelCase($name) . '\']';

			!next($rwProperties) === true ?: $selfBody .= ",\n";
		}

		$fromData->addBody("return new self(\n?\n);", [new PhpLiteral($selfBody)]);
	}

	private function addFromRowMethod()
	{
		$fromRow = $this->class->addMethod('fromRow')
			->setStatic()
			->setReturnType(IDatabaseData::class)
			->addComment('@todo Finish implementation.');

		$fromRow->addParameter('row')
			->setTypeHint('array');

		$selfBody = "return (new self(\n";

		$rwProperties = $this->definition['databaseCols']['rw'];

		foreach ($rwProperties as $name => $property) {

			$selfBody .= "\t\t\$data['" . $name . '\']';

			!next($rwProperties) === true ?: $selfBody .= ",\n";
		}

		$selfBody .= "\n\t)\n)";

		$roProperties = $this->definition['databaseCols']['ro'];

		foreach ($roProperties as $name => $property) {
			$selfBody .= "\n->set" . ucfirst($this->toCamelCase($name)) . '($row[\'' . $name . '\'])';
		}

		$selfBody .= ';';

		$fromRow->setBody($selfBody);
	}

	private function addToArrayMethod()
	{
		$toArray = $this->class->addMethod('toArray')
			->setReturnType('array')
			->setBody('return get_object_vars($this);');
	}

	private function addToDatabaseArrayMethod()
	{
		$toArray = $this->class->addMethod('toDatabaseArray')
			->setReturnType('array')
			->addComment('@todo Finish implementation.');

		$body = "return [\n";

		$rwProperties = $this->definition['databaseCols']['rw'];

		foreach ($rwProperties as $name => $property) {
			$body .= "\t" . '\'' . $name . '\' => $this->' . $this->toCamelCase($name) . ",\n";
		}

		$body .= '];';

		$toArray->setBody($body);
	}

	private function addGetter(string $name, array $propertyDefinition)
	{
		$this->class->addMethod('get' . ucfirst($this->toCamelCase($name)))
			->setReturnType($propertyDefinition['type'])
			->addBody('return $this->?;', [$this->toCamelCase($name)]);
	}

	private function addSetter(string $name, array $propertyDefinition)
	{
		$this->class->addMethod('set' . ucfirst($this->toCamelCase($name)))
			->addBody("\$this->? = $?;\n", [$this->toCamelCase($name), $this->toCamelCase($name)])
			->addBody('return $this;')
			->addParameter($name)
			->setTypeHint($propertyDefinition['type']);
	}

	private function addProperty(string $name, array $propertyDefinition)
	{
		if (substr($propertyDefinition['type'], 0, 1) === '\\') {
			$classReflection = new \ReflectionClass($propertyDefinition['type']);

			$this->namespace->addUse($classReflection->getName());

			$this->class->addProperty($this->toCamelCase($name))
				->setVisibility('private')
				->addComment("\n@var " . $classReflection->getName());
		} else {
			$this->class->addProperty($this->toCamelCase($name))
				->setVisibility('private')
				->addComment("\n@var " . $propertyDefinition['type']);
		}
	}


	private function getClassName()
	{
		return ucfirst($this->name);
	}

	private function getNamespace(string $concreteNamespace)
	{
		return $this->definition['namespace'] . '\\' . $concreteNamespace;
	}

	private function getRowFactoryNamespace()
	{
		return $this->getNamespace('Data');
	}

	private function getRowFactoryClassName()
	{
		return $this->getClassName() . 'Data';
	}

	private function getCollectionFactoryNamespace()
	{
		return $this->getNamespace('Collection');
	}

	private function getCollectionFactoryClassName()
	{
		return $this->getClassName() . 'Collection';
	}

	private function getDatabaseLowLevelStorageNamespace()
	{
		return $this->getNamespace('Storage');
	}

	private function getDatabaseLowLevelStorageClassName()
	{
		return $this->getClassName() . 'DatabaseLowLevelStorage';
	}

	private function toCamelCase($string)
	{
		return preg_replace('/[-\_]/', '', Strings::firstLower(Strings::capitalize($string)));
	}

}
