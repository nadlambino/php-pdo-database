<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Traits;

use Closure;
use Inspira\Database\Builder\Clauses\Where as WhereBuilder;
use Inspira\Database\Builder\Enums\Reserved;
use Inspira\Database\Builder\Raw;
use Inspira\Database\Builder\Select;
use PDO;

trait Where
{
	protected array $wheres = [];

	public function where(string|Closure|Raw $column, mixed $comparison = null, mixed $value = null): static
	{
		if ($column instanceof Raw) {
			return $this->addConditions(Reserved::WHERE, Reserved::AND, false, ['raw' => (string) $column]);
		}

		if ($column instanceof Closure) {
			return $this->addWhereGroup(Reserved::AND, $column);
		}

		$parameters = $this->getConditionalParams($column, $comparison, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function orWhere(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
		if ($column instanceof Closure) {
			return $this->addWhereGroup(Reserved::OR, $column);
		}

		$parameters = $this->getConditionalParams($column, $comparison, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function whereLike(string $column, string $value): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::LIKE, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function whereNotLike(string $column, string $value): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_LIKE, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function orWhereLike(string $column, string $value): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::LIKE, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function orWhereNotLike(string $column, string $value): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_LIKE, $value);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function whereNull(string $column): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function whereNotNull(string $column): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS_NOT, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function orWhereNull(string $column): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function orWhereNotNull(string $column): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IS_NOT, null, PDO::PARAM_NULL);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function whereBetween(string $column, mixed $lowerBound, mixed $upperBound): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::BETWEEN, [$lowerBound, $upperBound]);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function whereNotBetween(string $column, mixed $lowerBound, mixed $upperBound): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_BETWEEN, [$lowerBound, $upperBound]);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function orWhereBetween(string $column, mixed $lowerBound, mixed $upperBound): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::BETWEEN, [$lowerBound, $upperBound]);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function orWhereNotBetween(string $column, mixed $lowerBound, mixed $upperBound): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_BETWEEN, [$lowerBound, $upperBound]);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function whereIn(string $column, array $values): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IN, $values);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function whereNotIn(string $column, array $values): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_IN, $values);

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	public function orWhereIn(string $column, array $values): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::IN, $values);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function orWhereNotIn(string $column, array $values): static
	{
		$parameters = $this->getConditionalParams($column, Reserved::NOT_IN, $values);

		return $this->addConditions(Reserved::WHERE, Reserved::OR, false, $parameters);
	}

	public function whereHas(Select|string $table, string $tableColumn = '', string $parentTableColumn = ''): static
	{
		if ($table instanceof Select) {
			$sql = $table->toSql();
			$this->parameters = [...$this->parameters, ...$table->getParameters()];
			$exists = Reserved::EXISTS->value;

			return $this->addConditions(Reserved::WHERE, Reserved::AND, false, ['raw' => " $exists ($sql) "]);
		}

		$parameters = $this->getConditionalParams($tableColumn, '=', $parentTableColumn);
		$parameters['table'] = $table;

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	protected function addWhereGroup(?Reserved $operator, Closure $closure): static
	{
		// Pass the WhereBuilder object to the closure to ensure that it receives the builder
		// with only the methods that it needs and usable within the Where query clause
		$builder = new WhereBuilder($this->connection);
		$closure($builder);

		return $this->addConditions(Reserved::WHERE, $operator, true, $builder->getWheres());
	}

	protected function getWhereClause(): string
	{
		$conditions = $this->getConditions($this->wheres, Reserved::WHERE->value);

		return empty(trim($conditions)) ? '' : $this->concat(Reserved::WHERE->value, $conditions);
	}
}
