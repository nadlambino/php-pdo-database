<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Inspira\Database\QueryBuilder\Enums\Reserved;

trait OrderBy
{
	protected array $orders = [];

	public function orderAsc(string $column): static
	{
		return $this->addOrder(Reserved::ASC, $column);
	}

	public function orderDesc(string $column): static
	{
		return $this->addOrder(Reserved::DESC, $column);
	}

	protected function addOrder(Reserved $order, string $column): static
	{
		$this->orders[$this->getFormattedColumn($column)] = $order->value;

		return $this;
	}

	protected function getOrderClause(): string
	{
		$clause = '';
		$columns = array_keys($this->orders);
		$last = end($columns);
		foreach ($this->orders as $column => $order) {
			$clause .= implode(' ', [$column, $order]);
			$clause .= $column === $last ? '' : ', ';
		}

		return empty($clause) ? '' : implode(' ', [Reserved::ORDER_BY->value, $clause]);
	}
}
