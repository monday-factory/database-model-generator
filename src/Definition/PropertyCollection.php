<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use Ramsey\Collection\AbstractCollection;

class PropertyCollection extends AbstractCollection
{

	private string $collectionType;

	public function __construct(array $data)
	{
		$this->collectionType = Property::class;

		parent::__construct($data);
	}

	public function getType(): string
	{
		return $this->collectionType;
	}

}
