<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

class PhinxCommentPropertyOptions
{
	private string $typeClass;

	private bool $readOnly = false;

	public function __construct(private string $columnComment)
	{
		$this->parse();
	}

	private function parse()
	{
		if ($jsonResult = json_decode($this->columnComment, true)) {
			if (is_array($jsonResult)) {
				foreach ($jsonResult as $key => $value) {
					$this->mapProperty($key, $value);
				}
			}
		} else if (strlen($this->columnComment) > 1 && str_starts_with('\\', $this->columnComment)) {
			if (class_exists($this->columnComment) or interface_exists($this->columnComment)) {
				$this->mapProperty('typeClass', $this->columnComment);
			}
		}
	}

	private function mapProperty($property, $value): void
	{
		$methodName = 'set' . ucfirst($property);
		if (method_exists($this, $methodName)) {
			$this->$methodName($value);
		}
	}

	private function setTypeClass($typeClass)
	{
		$this->typeClass = $typeClass;
		return $this;
	}

	private function setReadOnly($readOnly)
	{
		$this->readOnly = filter_var($readOnly, FILTER_VALIDATE_BOOLEAN);
		return $this;
	}

	public function getTypeClass(): ?string
	{
		if (!isset($this->typeClass)) {
			return null;
		}

		return $this->typeClass;
	}

	public function isReadOnly(): bool
	{
		return $this->readOnly;
	}

}
