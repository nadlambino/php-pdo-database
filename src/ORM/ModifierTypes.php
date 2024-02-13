<?php

declare(strict_types=1);

namespace Inspira\Database\ORM;

enum ModifierTypes: string
{
	case ACCESSORS = 'accessors';
	case MUTATORS = 'mutators';
}
