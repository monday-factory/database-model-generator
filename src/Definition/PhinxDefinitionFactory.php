<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use MondayFactory\DatabaseModelGenerator\Mapper\CommonTypes;
use MondayFactory\DatabaseModelGenerator\Mapper\MapperObjectInterface;

class PhinxDefinitionFactory
{
	private CommonTypes $commonTypes;

	private TableCollection $tableCollection;

	private array $inputDef;

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

	public function __construct(
		array $definition,
		private ?array $meta = null,
	)
	{
		if (array_key_exists('tables', $definition)) {
			$definition = $definition['tables'];
		}

		$this->inputDef = $definition;
		$this->commonTypes = new CommonTypes();
	}

	public function create(): TableCollection
	{
		if (isset($this->tableCollection)) {
			return $this->tableCollection;
		}

		$tableCollection = new TableCollection([]);
		$meta = Meta::factory($this->meta);

		foreach ($this->inputDef as $tableName => $tableDef) {
			echo sprintf('Validating table %s.', $tableName) . PHP_EOL;
			$properties = new PropertyCollection([]);

			if ($tableName === 'phinxlog') {
				echo 'Table phinxlog cannot be generated. Skipping.' . PHP_EOL;

				continue;
			}

			if (! $tableDef['columns'] ?? false) {
				echo sprintf('Table %s does not have any columns. Skipping.', $tableName) . PHP_EOL;

				continue;
			}

			echo sprintf('Preparing properties for table %s.', $tableName) . PHP_EOL;
			$tablePrimary = null;

			foreach ($tableDef['columns'] as $columnName => $columnDef) {
				if ($columnDef['COLUMN_KEY'] === 'PRI') {
					$tablePrimary = $columnName;
				}

				if ($this->meta) {
					$propertyOptions = $meta->getTable($tableName)->getColumn($columnName);
				} else {
					$propertyOptions = $this->getCommentPropertyOptions($columnDef['COLUMN_COMMENT']);
				}

				$properties->add(
					new Property(
						$columnName,
						$this->getPropertyType($columnDef['DATA_TYPE']),
						filter_var($columnDef['IS_NULLABLE'], FILTER_VALIDATE_BOOLEAN),
						$this->getDefaultValue($columnDef),
						(int) $columnDef['CHARACTER_MAXIMUM_LENGTH'] ?? 0,
						readOnly: $propertyOptions->isReadOnly(),
						mapper: $propertyOptions->getTypeClass() ? $this->commonTypes->getMapper($propertyOptions->getTypeClass()) : null,
					)
				);
			}

			$tableCollection->add(new Table($tableName, $properties, $tablePrimary));
		}

		return $this->tableCollection = $tableCollection;
	}

	private function getCommentPropertyOptions($columnComment): PhinxCommentPropertyOptions
	{
		return new PhinxCommentPropertyOptions($columnComment);
	}

	private function getDefaultValue($columnDef)
	{
		$computedType = $this->getPropertyType($columnDef['DATA_TYPE']);

		if (class_exists($computedType) || interface_exists($computedType)) {
			return null;
		}

		if ($this->commonTypes->getMapper($columnDef['COLUMN_COMMENT']) instanceof MapperObjectInterface) {
			return null;
		}

		return $columnDef['COLUMN_DEFAULT'] ?? null;
	}

	private function getPropertyType(string $type): string
	{
		if (!array_key_exists($type, $this->typeMap)) {
			throw new \InvalidArgumentException(sprintf('Type %s is not in map.', $type));
		}

		return $this->typeMap[$type];
	}

}
