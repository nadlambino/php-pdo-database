<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\Traits\Join;
use Inspira\Database\QueryBuilder\Traits\Where;

class Delete extends Base
{
	use Where, Join;

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
		$this->cleanUp();
		$this->wheres = [];
		$this->joins = [];

		return $this;
	}
}
