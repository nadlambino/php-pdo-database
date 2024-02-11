<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use Inspira\Database\QueryBuilder\Enums\Reserved;
use Inspira\Database\QueryBuilder\Traits\Join;
use Inspira\Database\QueryBuilder\Traits\Where;
use InvalidArgumentException;

class UpdateQuery extends AbstractQuery
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

		$sql = implode(
			' ',
			[
				Reserved::UPDATE->value,
				pdo_quote($this->table),
				$this->getJoinClause(),
				Reserved::SET->value,
				$this->getColumnClause(),
				$this->getWhereClause()
			]
		);

		return normalize_whitespace($sql);
	}

	private function getColumnClause(): string
	{
		$clause = '';
		$glue = ', ';

		foreach ($this->data as $column => $value) {
			if (is_int($column)) {
				throw new InvalidArgumentException('Values should be an associative array where key is the column name');
			}

			$placeholder = $this->generatePlaceholder($column);
			$clause .= implode(' ', [pdo_quote($column), '=', $placeholder, $glue]);
			$this->addParameter($placeholder, $value);
		}

		return trim($clause, $glue);
	}

	public function clean(): static
	{
		$this->cleanUp();
		$this->data = [];
		$this->wheres = [];
		$this->joins = [];

		return $this;
	}
}
