<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Closure;
use Inspira\Database\QueryBuilder\Clauses\WhereClause;
use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\RawQuery;
use Inspira\Database\QueryBuilder\SelectQuery;
use PDO;

trait Where
{
	protected array $wheres = [];

	public function whereRaw(string $query): static
	{
		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, ['raw' => (string) (new RawQuery($query, connection: $this->connection))]);
	}

	public function where(string|Closure $column, mixed $comparison = null, mixed $value = null): static
	{
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

	public function whereExists(SelectQuery|string $table, ?string $tableColumn = null, ?string $parentTableColumn = null): static
	{
		return $this->handleWhereExists($table, $tableColumn, $parentTableColumn);
	}

	public function whereNotExists(SelectQuery|string $table, ?string $tableColumn = null, ?string $parentTableColumn = null): static
	{
		return $this->handleWhereExists($table, $tableColumn, $parentTableColumn, false);
	}

	protected function handleWhereExists(SelectQuery|string $table, ?string $tableColumn = null, ?string $parentTableColumn = null, bool $exists = true): static
	{
		if ($table instanceof SelectQuery) {
			$sql = $table->toSql();
			$this->parameters = [...$this->parameters, ...$table->getParameters()];
			$existsQuery = $exists ? Reserved::EXISTS->value : Reserved::NOT_EXISTS->value;

			return $this->addConditions(Reserved::WHERE, Reserved::AND, false, ['raw' => " $existsQuery ( $sql ) "]);
		}

		$tableColumn ??= singularize($this->table) . '_id';
		$parentTableColumn ??= 'id';
		$parameters = $this->getConditionalParams($tableColumn, '=', $parentTableColumn);
		$parameters['table'] = $table;
		$parameters['exists'] = $exists;

		return $this->addConditions(Reserved::WHERE, Reserved::AND, false, $parameters);
	}

	protected function addWhereGroup(?Reserved $operator, Closure $closure): static
	{
		// Pass the WhereClause object to the closure to ensure that it receives the builder
		// with only the methods that it needs and usable within the Where query clause
		$builder = new WhereClause($this->connection);
		$closure($builder);

		return $this->addConditions(Reserved::WHERE, $operator, true, $builder->getWheres());
	}

	protected function getWhereClause(): string
	{
		$conditions = $this->getConditions($this->wheres, Reserved::WHERE->value);

		return empty(trim($conditions)) ? '' : implode(' ', [Reserved::WHERE->value, $conditions]);
	}
}
