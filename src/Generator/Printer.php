<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Generator;

use Nette\PhpGenerator\Printer as NettePrinter;

class Printer extends NettePrinter
{

	public function __construct()
	{
		$this->linesBetweenMethods = 1;
	}

}
