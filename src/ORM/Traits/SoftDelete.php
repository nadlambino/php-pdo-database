<?php

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\QueryBuilder\Query;
use Inspira\Database\QueryBuilder\UpdateQuery;

/**
 * @property-read Query $query
 * @property-read string $table
 * @method addQuery(string $method, array $arguments)
 */
trait SoftDelete
{
	protected function softDelete(): UpdateQuery
	{
		return $this->query
			->update($this->table)
			->set([
				static::DELETED_AT => date(static::DATE_TIME_FORMAT)
			]);
	}

	public function withTrashed(): static
	{
		foreach($this->queries as $index => $clause) {
			if ($clause['method'] === 'whereNull' && in_array(static::DELETED_AT, $clause['arguments'])) {
				unset($this->queries[$index]);
				break;
			}
		}

		return $this;
	}

	public function onlyTrashed(): static
	{
		foreach($this->queries as $index => $clause) {
			if ($clause['method'] === 'whereNull' && in_array(static::DELETED_AT, $clause['arguments'])) {
				unset($this->queries[$index]);
				$this->addQuery('whereNotNull', [static::DELETED_AT]);
				break;
			}
		}

		return $this;
	}
}
