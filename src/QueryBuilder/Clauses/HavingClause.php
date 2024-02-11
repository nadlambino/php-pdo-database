<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Clauses;

use Inspira\Database\QueryBuilder\Traits;
use PDO;

/**
 * The purpose of this class is to be use as a wrapper for the Inspira\Database\Traits\Having trait
 * This is the one that will be pass to a closure that needs a builder with only the Having methods
 */
class HavingClause
{
	use Traits\Having, Traits\QueryHelper;

	public function __construct(protected PDO $connection)
	{
	}

	public function getHavings(): array
	{
		return $this->havings;
	}
}
