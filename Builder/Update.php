<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

use Inspira\Database\Builder\Enums\Reserved;
use Inspira\Database\Builder\Traits\Where;
use InvalidArgumentException;
use Inspira\Database\Builder\Traits\Join;

class Update extends Base
{
	use Where, Join;

	protected array $data = [];

	public function set(array $data): static
	{
		$this->data = $data;

		return $this;
	}

	public function toSql(): string
	{
		if (empty($this->table)) {
			return '';
		}

		$sql = $this->concat(
			Reserved::UPDATE->value,
			$this->quote($this->table),
			$this->getJoinClause(),
			Reserved::SET->value,
			$this->getColumnClause(),
			$this->getWhereClause()
		);

		return $this->trimWhiteSpace($sql);
	}

	private function getColumnClause(): string
	{
		$clause = '';
		$glue   = ', ';

		foreach ($this->data as $column => $value) {
			if (is_int($column)) {
				throw new InvalidArgumentException('Values should be an associative array where key is the column name');
			}

			$placeholder = ":$column" . '_' . count($this->getParameters());
			$clause     .= $this->concat($this->quote($column), '=', $placeholder, $glue);
			$this->addParameter($placeholder, $value);
		}

		return trim($clause, $glue);
	}

	public function clean(): static
	{
		$this->cleanUp();
		$this->data   = [];
		$this->wheres = [];
		$this->joins  = [];

		return $this;
	}
}
