<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Inspira\Database\QueryBuilder\Enums\Reserved;

trait GroupBy
{
	protected array $groups = [];

	public function groupBy(string $column): static
	{
		$this->groups[] = $this->getFormattedColumn($column);
		$this->addColumn($column);

		return $this;
	}

	protected function getGroupByClause(): string
	{
		$glue = ', ';

		return empty($this->groups)
			? ''
			: Reserved::GROUP_BY->value . ' ' . trim(implode($glue, $this->groups), $glue);
	}
}
