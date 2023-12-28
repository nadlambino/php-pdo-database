<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Traits;

use Closure;
use Inspira\Database\Builder\Clauses\Having as HavingBuilder;
use Inspira\Database\Builder\Enums\Reserved;
use PDO;

trait Having
{
	protected array $havings = [];

	public function having(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		if ($column instanceof Closure) {
			return $this->addHavingGroup(Reserved::AND, $column);
		}

		$parameters = $this->getConditionalParams($column, $comparison, $value);

		return $this->addConditions(Reserved::HAVING, Reserved::AND, false, $parameters);
	}


	public function orHaving(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		if ($column instanceof Closure) {
			return $this->addHavingGroup(Reserved::HAVING, $column);
		}

		$parameters = $this->getConditionalParams($column, $comparison, $value);

		return $this->addConditions(Reserved::HAVING, Reserved::OR, false, $parameters);
	}

	public function havingNull(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::HAVING, Reserved::AND, false, $parameters);
	}

	public function orHavingNull(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::HAVING, Reserved::OR, false, $parameters);
	}

	public function havingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS_NOT, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::HAVING, Reserved::AND, false, $parameters);
	}

	public function orHavingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS_NOT, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::HAVING, Reserved::OR, false, $parameters);
	}

	protected function addHavingGroup(?Reserved $operator, Closure $closure): static
	{
		// Pass the HavingBuilder object to the closure to ensure that it receives the builder
		// with only the methods that it needs and usable within the Having query clause
		$builder = new HavingBuilder($this->connection);
		$closure($builder);

		return $this->addConditions(Reserved::HAVING, $operator, true, $builder->getHavings());
	}

	protected function getHavingClause(): string
	{
		$conditions = $this->getConditions($this->havings, Reserved::HAVING->value);

		return empty(trim($conditions)) ? '' : $this->concat(Reserved::HAVING->value, $conditions);
	}
}
