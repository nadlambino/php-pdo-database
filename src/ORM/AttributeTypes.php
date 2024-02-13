<?php

declare(strict_types=1);

namespace Inspira\Database\ORM;

enum AttributeTypes: string
{
	case ORIGINAL = 'original';
	case OLD = 'old';
	case ATTRIBUTES = 'attributes';
}
