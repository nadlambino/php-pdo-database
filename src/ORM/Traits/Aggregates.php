<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use ReturnTypeWillChange;

trait Aggregates
{
	#[ReturnTypeWillChange]
	public function count(): int
	{
		$method = __FUNCTION__;
		$alias = $method . '_' . $this->primaryKey;
		$query = $this->query->select();

		$this->attachQueries($query);

		return (int) $query->count($this->primaryKey, $alias)->first()?->$alias ?? 0;
	}

	public function sum(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachQueries($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function avg(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachQueries($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function min(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachQueries($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function max(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachQueries($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}
}
