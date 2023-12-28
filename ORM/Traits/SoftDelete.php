<?php

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\Builder\Query;
use Inspira\Database\Builder\Update;

/**
 * @property-read Query $query
 * @property-read string $table
 * @method appendQueryClauses(mixed $query)
 * @method addQueryClause(string $name, array $arguments)
 */
trait SoftDelete
{
	protected string $deletedAt = 'deleted_at';

	protected function softDelete(): Update
	{
		return $this->query
			->update($this->table)
			->set([
				$this->deletedAt => date('Y-m-d H:i:s')
			]);
	}
}
