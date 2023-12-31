<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

use Inspira\Database\Builder\Enums\Reserved;
use Inspira\Database\Builder\Traits\Where;
use Inspira\Database\Builder\Traits\Join;

class Delete extends Base
{
	use Where, Join;

	public function toSql(): string
	{
		if (empty($this->table)) {
			return '';
		}

		$join  = $this->getJoinClause();
		$table = $this->quote($this->table);
		$sql   = $this->concat(
			Reserved::DELETE->value,
			empty($join) ? '' : $table,
			Reserved::FROM->value,
			$table,
			$join,
			$this->getWhereClause()
		);

		return $this->trimWhiteSpace($sql);
	}

	public function clean(): static
	{
		$this->cleanUp();
		$this->wheres = [];
		$this->joins  = [];

		return $this;
	}
}
