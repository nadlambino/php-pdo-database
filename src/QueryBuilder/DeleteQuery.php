<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\Traits\Helpers;
use Inspira\Database\QueryBuilder\Traits\Join;
use Inspira\Database\QueryBuilder\Traits\Where;

class DeleteQuery extends AbstractQuery
{
	use Where, Join, Helpers;

	public function toSql(): string
	{
		if (empty($this->table)) {
			return '';
		}

		$join = $this->getJoinClause();
		$table = pdo_quote($this->table);
		$sql = implode(
			' ',
			[
				Reserved::DELETE->value,
				empty($join) ? '' : $table,
				Reserved::FROM->value,
				$table,
				$join,
				$this->getWhereClause()
			]
		);

		return normalize_whitespace($sql);
	}

	public function clean(): static
	{
		$this->wheres = [];
		$this->joins = [];
		$this->setParameters([]);
		parent::clean();

		return $this;
	}
}
