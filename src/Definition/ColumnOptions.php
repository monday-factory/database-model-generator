<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class ColumnOptions
{

	public function __construct(
		private readonly string $typeClass,
		private readonly bool $readOnly = false,
	) {}

	public static function factory(array $meta): self
	{
		foreach ($meta as $metaName => $value) {
			if (!PropertyOptions::tryFrom("readOnly") instanceof PropertyOptions) {
				trigger_error(sprintf('Invalid meta name "%s"', $metaName), E_USER_WARNING);
				continue;
			}
		}

		return new self(
			$meta['typeClass'] ?? '',
			$meta['readOnly'] ?? false,
		);
	}

	public function isReadOnly(): bool
	{
		return $this->readOnly;
	}

	public function getTypeClass(): ?string
	{
		return $this->typeClass;
	}
}
