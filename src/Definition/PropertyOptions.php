<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Definition;

enum PropertyOptions: string
{
	case typeClass = "typeClass";
	case readOnly = "readOnly";
}
