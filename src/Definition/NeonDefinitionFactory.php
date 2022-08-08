<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use MondayFactory\DatabaseModelGenerator\Mapper\CommonTypes;
use MondayFactory\DatabaseModelGenerator\Mapper\MapperObjectInterface;
use Nette\Neon\Neon;

class NeonDefinitionFactory
{
	private CommonTypes $commonTypes;

	private TableCollection $tableCollection;

	private array $inputDef;

	private $tablePrimary;

	private $typeMap = [
		'tinyint' => 'int',
		'smallint' => 'int',
		'mediumint' => 'int',
		'int' => 'int',
		'bigint' => 'int',
		'float' => 'float',
		'double' => 'float',
		'real' => 'float',
		'decimal' => 'float',

		'bool' => 'bool',
		'boolean' => 'bool',

		'char' => 'string',
		'varchar' => 'string',
		'tinytext' => 'string',
		'text' => 'string',
		'mediumtext' => 'string',
		'longtext' => 'string',
		'blob' => 'string',
		'longblob' => 'string',

		'json' => 'json',
		'enum' => 'string',
		'set' => 'string',

		'date' => \DateTimeInterface::class,
		'datetime' => \DateTimeInterface::class,
		'timestamp' => 'number',
	];

	public function __construct(string $neonPath)
	{
		$neonContent = file_get_contents($neonPath);

		if ($neonContent === false) {
			throw new \UnexpectedValueException('Neon file can not be read.');
		}

		$neonDef = Neon::decode($neonContent);

		$this->inputDef = $neonDef;
		$this->commonTypes = new CommonTypes();
	}

	public function create(): TableCollection
	{
		if (isset($this->tableCollection)) {
			return $this->tableCollection;
		}

		$tableCollection = new TableCollection([]);
		$properties = new PropertyCollection([]);

		$this->processCols(true, $properties);
		$this->processCols(false, $properties);

		$tableCollection->add(new Table($this->inputDef['databaseTable'], $properties, $this->tablePrimary));

		return $this->tableCollection = $tableCollection;
	}

	private function processCols(bool $readOnly, PropertyCollection $propertyCollection)
	{
		foreach ($this->inputDef['databaseCols'][$readOnly ? 'ro' : 'rw'] ?? [] as $columnName => $columnDef) {
			$readOnly = false;

			if ($this->inputDef['databaseTableId'] === $columnName) {
				$this->tablePrimary = $columnName;
			}

			$propertyCollection->add(new Property(
				$columnName,
				$this->getPropertyType($columnDef['type']),
				filter_var($columnDef['nullbale'] ?? false, FILTER_VALIDATE_BOOLEAN),
				$this->getDefaultValue($columnDef),
				$columnDef['max_length'] ?? 0,
				$readOnly,
				$this->commonTypes->getMapper($columnDef['type']),
			));
		}
	}

	private function getCommentPropertyOptions($columnComment): PhinxCommentPropertyOptions
	{
		return new PhinxCommentPropertyOptions($columnComment);
	}

	private function getDefaultValue($columnDef)
	{
		$computedType = $this->getPropertyType($columnDef['type']);

		if (class_exists($computedType) || interface_exists($computedType)) {
			return null;
		}

		if ($this->commonTypes->getMapper($columnDef['type']) instanceof MapperObjectInterface) {
			return null;
		}

		return $columnDef['default'] ?? null;
	}

	private function getPropertyType(string $type): string
	{
		if (!array_key_exists($type, $this->typeMap)) {

			return 'string';
		}

		return $this->typeMap[$type];
	}

}
