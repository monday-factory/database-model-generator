<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

use MondayFactory\DatabaseModelGenerator\Mapper\MapperObjectInterface;
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

	public function sortRequired(): self
	{
		$collection = clone $this;

		usort(
			$collection->data,
			/**
			 * @var Property $a
			 * @var Property $b
			 */
			function ($a, $b): int {
				$propADefault = $propBDefault = false;

				if ($a->hasDefaultValue()) {
					$propADefault = true;
				}

				if ($b->hasDefaultValue()) {
					$propBDefault = true;
				}

				if ($propADefault == $propBDefault) {
					return 0;
				}

				if ($propADefault && !$propBDefault) {
					return 1;
				}

				return -1;
			}
		);

		return $collection;
	}

}
