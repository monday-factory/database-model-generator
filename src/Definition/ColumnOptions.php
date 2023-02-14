<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class ColumnOptions
{

	public function __construct(
		private readonly string $typeClass,
		private readonly bool $readOnly = false,
	) {}

	public function factory(array $meta): self
	{
		foreach ($meta as $metaName => $value) {
			if (!PropertyOptions::isValid($metaName)) {
				trigger_error(sprintf('Invalid meta name "%s"', $metaName), E_USER_WARNING);
				continue;
			}
		}

		if (!class_exists($meta['typeClass'])) {
			trigger_error(sprintf('Invalid type class "%s"', $meta['typeClass']), E_USER_WARNING);
		}

		return new self(
			$meta['typeClass'] ?? null,
			$meta['readonly'] ?? false,
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
