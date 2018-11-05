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
			$this->addGetter($name, $property);
		}

		foreach ($this->definition['databaseCols']['rw'] as $name => $property) {
			$this->addSetter($name, $property);
		}

			dump($this->namespace->getUses());

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

		$selfBody = "return (new self(\n";

		$rwProperties = $this->definition['databaseCols']['rw'];

		foreach (array_keys($rwProperties) as $name) {

			$selfBody .= "\t\t\$data['" . $name . '\']';

			next($rwProperties) === false ?: $selfBody .= ",\n";
		}

		$selfBody .= "\n\t)\n)";

		$roProperties = $this->definition['databaseCols']['ro'];

		foreach (array_keys($roProperties) as $name) {
			$selfBody .= "\n->set" . ucfirst($this->toCamelCase((string) $name)) . '($row[\'' . $name . '\'])';
		}

		$selfBody .= ';';

		$fromRow->setBody($selfBody);
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

		foreach (array_keys($rwProperties) as $name) {
			$body .= "\t" . '\'' . $name . '\' => $this->' . $this->toCamelCase((string) $name) . ",\n";
		}

		$body .= '];';

		$toArray->setBody($body);
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
