<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Enums;

enum Aggregates: string
{
	case COUNT = 'COUNT';
	case AVG = 'AVG';
	case SUM = 'SUM';
	case MIN = 'MIN';
	case MAX = 'MAX';
}
