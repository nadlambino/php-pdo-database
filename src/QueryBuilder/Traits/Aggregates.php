<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Inspira\Database\QueryBuilder\Enums\Aggregates as Agg;
use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\Raw;

trait Aggregates
{
	protected array $counts = [];

	protected array $sums = [];

	protected array $avgs = [];

	protected array $mins = [];

	protected array $maxs = [];

	public function count(Raw|string|null $column = null, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::COUNT, $column, $alias);
	}

	public function sum(Raw|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::SUM, $column, $alias);
	}

	public function avg(Raw|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::AVG, $column, $alias);
	}

	public function min(Raw|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::MIN, $column, $alias);
	}

	public function max(Raw|string $column, ?string $alias = null): static
	{
		return $this->addAggregate(Agg::MAX, $column, $alias);
	}

	protected function addAggregate(Agg $aggregate, Raw|string|null $column, Raw|string $alias = null): static
	{
		/** @var array $property */
		$property = strtolower($aggregate->value . 's');

		if (empty($column) && $aggregate === Agg::COUNT) {
			$this->$property[Reserved::ALL->value] = Reserved::ALL->value;
		} else {
			$alias = $alias ?? (string)$column;
			$this->$property[pdo_quote($alias)] = $column instanceof Raw ? $column : $this->getFormattedColumn($column);
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
