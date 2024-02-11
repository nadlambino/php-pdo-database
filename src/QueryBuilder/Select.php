<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use Closure;
use Inspira\Database\QueryBuilder\Clauses\Select as SelectBuilder;
use Inspira\Database\QueryBuilder\Enums\Aggregates as Agg;
use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\Traits\Aggregates;
use Inspira\Database\QueryBuilder\Traits\GroupBy;
use Inspira\Database\QueryBuilder\Traits\Having;
use Inspira\Database\QueryBuilder\Traits\Join;
use Inspira\Database\QueryBuilder\Traits\OrderBy;
use Inspira\Database\QueryBuilder\Traits\Where;
use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

class Select extends Base
{
	use Where, OrderBy, GroupBy, Having, Aggregates, Join;

	protected bool $distinct = false;

	protected ?int $limit = null;

	protected ?int $offset = null;

	protected array $unions = [];

	public function __construct(
		protected array              $columns,
		protected PDO                $connection,
		protected InflectorInterface $inflector,
		protected string|null        $model = null
	)
	{
		parent::__construct($this->connection, $this->inflector);
	}

	public function distinct(): static
	{
		$this->distinct = true;

		return $this;
	}

	public function from(string $table, ?string $alias = null): static
	{
		$this->setTable($table);
		$this->tableAlias = $alias;

		return $this;
	}

	public function get(): array
	{
		$this->execute();
		$this->setFetchMode();

		return $this->statement?->fetchAll();
	}

	public function first(): mixed
	{
		$this->execute();
		$this->setFetchMode();
		$data = $this->statement?->fetch();

		return $data === false ? null : $data;
	}

	public function limit(int $limit): static
	{
		$this->limit = $limit;
		$this->addParameter($this->generatePlaceholder('limit', suffix: ''), $limit);

		return $this;
	}

	public function offset(int $offset): static
	{
		$this->offset = $offset;
		$this->addParameter($this->generatePlaceholder('offset', suffix: ''), $offset);

		return $this;
	}

	public function union(Closure $closure): static
	{
		// Pass the SelectBuilder object to the closure to ensure that it receives the builder
		// with only the methods that it needs and usable within the Select query clause
		$builder = new SelectBuilder($this->connection, $this->inflector);
		$closure($builder);

		$this->unions[] = $builder->toSql();
		$this->mergeParameters($builder->getParameters());

		return $this;
	}

	public function toSql(): string
	{
		if (empty($this->table)) {
			return '';
		}

		$sql = implode(
			' ',
			[
				$this->getSelectClause(),
				$this->getJoinClause(),
				$this->getWhereClause(),
				$this->getGroupByClause(),
				$this->getHavingClause(),
				$this->getOrderClause(),
				$this->getUnionClause(),
				$this->getLimitClause(),
				$this->getOffsetClause(),
			]
		);

		return normalize_whitespace($sql);
	}

	private function setFetchMode(): void
	{
		if (empty($this->model)) {
			$this->statement->setFetchMode(PDO::FETCH_ASSOC);
			return;
		}

		$this->statement->setFetchMode(PDO::FETCH_CLASS, $this->model);
	}

	private function getSelectClause(): string
	{
		$glue = ', ';
		$columns = implode($glue, [
			$this->getColumns(),
			$this->getAggregate(Agg::COUNT),
			$this->getAggregate(Agg::SUM),
			$this->getAggregate(Agg::AVG),
			$this->getAggregate(Agg::MIN),
			$this->getAggregate(Agg::MAX),
		]);
		$columns = trim($columns, $glue);
		$columns = empty($columns) ? Reserved::ALL->value : $columns;
		$columns = $this->distinct ? Reserved::DISTINCT->value . ' ' . $columns : $columns;

		return implode(
			' ',
			[
				Reserved::SELECT->value,
				$columns,
				Reserved::FROM->value,
				pdo_quote($this->table),
				pdo_quote($this->tableAlias)
			]
		);
	}

	private function addColumn(string $column, ?string $alias = null): void
	{
		if (in_array($column, $this->columns)) {
			return;
		}

		$this->columns[] = [$column => $alias];
	}

	private function getColumns(): string
	{
		$flatten = flatten($this->columns);
		$columns = array_keys($flatten);
		$aliases = array_values($flatten);

		$aliased = [];
		foreach ($columns as $key => $column) {
			$alias = empty($aliases[$key]) ? $column : $aliases[$key];

			if (is_int($column) || $column === $alias) {
				$aliased[] = $this->getFormattedColumn($alias);
				continue;
			}

			$aliased[] = implode(
				' ',
				[
					$this->getFormattedColumn($column),
					Reserved::AS->value,
					pdo_quote($alias)
				]
			);
		}

		return implode(', ', $aliased);
	}

	private function getLimitClause(): string
	{
		if (is_null($this->limit)) {
			return '';
		}

		return implode(
			' ',
			[
				Reserved::LIMIT->value,
				$this->generatePlaceholder('limit', suffix: '')
			]
		);
	}

	private function getOffsetClause(): string
	{
		if (is_null($this->offset)) {
			return '';
		}

		return implode(
			' ',
			[
				Reserved::OFFSET->value,
				$this->generatePlaceholder('offset', suffix: '')
			]
		);
	}

	private function getUnionClause(): string
	{
		$clause = '';

		foreach ($this->unions as $union) {
			$clause .= implode(
				' ',
				[
					' ',
					Reserved::UNION->value,
					$union
				]
			);
		}

		return $clause;
	}

	public function clean(): static
	{
		$this->cleanUp();
		$this->distinct = false;
		$this->limit = null;
		$this->offset = null;
		$this->columns = [];
		$this->unions = [];
		$this->wheres = [];
		$this->orders = [];
		$this->groups = [];
		$this->havings = [];
		$this->counts = [];
		$this->sums = [];
		$this->avgs = [];
		$this->mins = [];
		$this->maxs = [];
		$this->joins = [];

		return $this;
	}
}
