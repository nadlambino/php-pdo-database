<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Clauses;

use Inspira\Database\Builder\Traits;
use PDO;

/**
 * The purpose of this class is to be use as a wrapper for the Inspira\Database\Traits\Having trait
 * This is the one that will be pass to a closure that needs a builder with only the Having methods
 */
class Having
{
	use Traits\Having, Traits\Helpers;

	public function __construct(protected PDO $connection) { }

	public function getHavings(): array
	{
		return $this->havings;
	}
}
