<?php

namespace Inspira\Database\ORM\Traits;

use Inspira\Collection\Collection;
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

	public function withTrashed()
	{
		foreach($this->clauses as $index => $clause) {
			if ($clause['name'] === 'whereNull' && in_array('deleted_at', $clause['arguments'])) {
				unset($this->clauses[$index]);
				break;
			}
		}

		return $this;
	}

	public function onlyTrashed()
	{
		foreach($this->clauses as $index => $clause) {
			if ($clause['name'] === 'whereNull' && in_array('deleted_at', $clause['arguments'])) {
				unset($this->clauses[$index]);
				array_unshift($this->clauses, ['name' => 'whereNotNull', 'arguments' => ['deleted_at']]);
				break;
			}
		}

		return $this;
	}
}
