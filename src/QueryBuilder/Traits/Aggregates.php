<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Inspira\Database\QueryBuilder\Enums\Aggregates as Agg;
use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\RawQuery;

trait Aggregates
{
	protected array $counts = [];

	protected array $sums = [];

	protected array $avgs = [];

	protected array $mins = [];

	protected array $maxs = [];

	public function count(RawQuery|string|null $column = null, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::COUNT, $column, $alias);
	}

	public function sum(RawQuery|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::SUM, $column, $alias);
	}

	public function avg(RawQuery|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::AVG, $column, $alias);
	}

	public function min(RawQuery|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::MIN, $column, $alias);
	}

	public function max(RawQuery|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::MAX, $column, $alias);
	}

	protected function addAggregate(Agg $aggregate, RawQuery|string|null $column, RawQuery|string $alias = null): static
	{
		/** @var array $property */
		$property = strtolower($aggregate->value . 's');

		if (empty($column) && $aggregate === Agg::COUNT) {
			$this->$property[Reserved::ALL->value] = Reserved::ALL->value;
		} else {
			$alias = $alias ?? (string)$column;
			$this->$property[pdo_quote($alias)] = $column instanceof RawQuery ? $column : $this->getFormattedColumn($column);
		}

		return $this;
	}

	protected function getAggregate(Agg $aggregate): string
	{
		$clause = '';
		$glue = ', ';
		$property = strtolower($aggregate->value . 's');
		$values = $this->$property ?? [];

		foreach ($values as $alias => $column) {
			$alias = $alias === (string)$column ? '' : ' ' . Reserved::AS->value . ' ' . $alias;
			$clause .= $aggregate->value . '(' . $column . ')' . $alias . $glue;
		}

		return trim($clause, $glue);
	}
}
