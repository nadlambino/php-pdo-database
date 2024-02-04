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
		$alias = $method . '_' . $this->pk;
		$query = $this->query->select();

		$this->attachClauses($query);

		return (int) $query->count($this->pk, $alias)->first()?->$alias ?? 0;
	}

	public function sum(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachClauses($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function avg(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachClauses($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function min(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachClauses($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}

	public function max(string $column): float
	{
		$method = __FUNCTION__;
		$alias = $method . "_$column";
		$query = $this->query->select();

		$this->attachClauses($query);

		return (float) $query->$method($column, $alias)->first()?->$alias ?? 0;
	}
}
