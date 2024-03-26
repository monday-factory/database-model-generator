<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Setting;

enum NormalizeName: string
{
	case camelCase = 'camelCase';
	case PascalCase = 'PascalCase';
	case snake_case = 'snake_case';
}
