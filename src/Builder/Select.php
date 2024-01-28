<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

use Closure;
use Inspira\Database\Builder\Clauses\Select as SelectBuilder;
use Inspira\Database\Builder\Enums\Aggregates as Agg;
use Inspira\Database\Builder\Enums\Reserved;
use Inspira\Database\Builder\Traits\Aggregates;
use Inspira\Database\Builder\Traits\GroupBy;
use Inspira\Database\Builder\Traits\Having;
use Inspira\Database\Builder\Traits\Join;
use Inspira\Database\Builder\Traits\OrderBy;
use Inspira\Database\Builder\Traits\Where;
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
		$this->addParameter(':limit', $limit);

		return $this;
	}

	public function offset(int $offset): static
	{
		$this->offset = $offset;
		$this->addParameter(':offset', $offset);

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

		$sql = $this->concat(
			$this->getSelectClause(),
			$this->getJoinClause(),
			$this->getWhereClause(),
			$this->getGroupByClause(),
			$this->getHavingClause(),
			$this->getOrderClause(),
			$this->getUnionClause(),
			$this->getLimitClause(),
			$this->getOffsetClause(),
		);

		return $this->trimWhiteSpace($sql);
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

		return $this->concat(
			Reserved::SELECT->value,
			$columns,
			Reserved::FROM->value,
			$this->quote($this->table),
			$this->quote($this->tableAlias)
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

			$aliased[] = $this->concat($this->getFormattedColumn($column), Reserved::AS->value, $this->quote($alias));
		}

		return implode(', ', $aliased);
	}

	private function getLimitClause(): string
	{
		if (is_null($this->limit)) {
			return '';
		}

		return $this->concat(Reserved::LIMIT->value, ':limit');
	}

	private function getOffsetClause(): string
	{
		if (is_null($this->offset)) {
			return '';
		}

		return $this->concat(Reserved::OFFSET->value, ':offset');
	}

	private function getUnionClause(): string
	{
		$clause = '';

		foreach ($this->unions as $union) {
			$clause .= $this->concat(' ', Reserved::UNION->value, $union);
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
