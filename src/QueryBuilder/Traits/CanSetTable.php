<?php

namespace Inspira\Database\QueryBuilder\Traits;

trait CanSetTable
{
	protected readonly string $table;

	protected ?string $tableAlias = null;

	protected function setTable(?string $table): void
	{
		if (!empty($table)) {
			$this->table = $table;
		}
	}

	protected function cleanTable(): void
	{
		$this->table = null;
		$this->tableAlias = null;
	}
}
