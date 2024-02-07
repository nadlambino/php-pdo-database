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
	protected function softDelete(): Update
	{
		return $this->query
			->update($this->table)
			->set([
				static::DELETED_AT => date('Y-m-d H:i:s')
			]);
	}

	public function withTrashed(): static
	{
		foreach($this->clauses as $index => $clause) {
			if ($clause['method'] === 'whereNull' && in_array(static::DELETED_AT, $clause['arguments'])) {
				unset($this->clauses[$index]);
				break;
			}
		}

		return $this;
	}

	public function onlyTrashed(): static
	{
		foreach($this->clauses as $index => $clause) {
			if ($clause['method'] === 'whereNull' && in_array(static::DELETED_AT, $clause['arguments'])) {
				unset($this->clauses[$index]);
				array_unshift($this->clauses, ['method' => 'whereNotNull', 'arguments' => [static::DELETED_AT]]);
				break;
			}
		}

		return $this;
	}
}
