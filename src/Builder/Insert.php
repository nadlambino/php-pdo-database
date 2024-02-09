<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

use Inspira\Database\Builder\Enums\Reserved;
use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

class Insert extends Base
{
	public function __construct(protected array $data, protected PDO $connection, protected InflectorInterface $inflector)
	{
		parent::__construct($this->connection, $this->inflector);
	}

	public function into(string $table): static
	{
		$this->setTable($table);

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
				Reserved::INSERT_INTO->value,
				pdo_quote($this->table),
				$this->getColumnClause(),
				Reserved::VALUES->value,
				$this->getValuesClause()
			]
		);

		return normalize_whitespace($sql);
	}

	private function getColumnClause(): string
	{
		$data = is_multi_array($this->data) ? $this->data[0] : $this->data;
		$clause = '';
		$glue = ', ';

		foreach ($data as $column => $value) {
			$clause .= pdo_quote($column) . $glue;
		}

		$clause = trim($clause, $glue);

		return empty($data) ? '' : "($clause)";
	}

	private function getValuesClause(): string
	{
		$isMultiDimension = is_multi_array($this->data);
		$clause = '';
		$glue = ', ';

		foreach ($this->data as $column => $value) {
			if ($isMultiDimension) {
				$subClause = $this->buildSubClause($value);
				$clause .= "($subClause)" . $glue;
			} else {
				$placeholder = $this->generatePlaceholder($column);
				$clause .= $placeholder . $glue;
				$this->addParameter($placeholder, $value);
			}
		}

		$clause = trim($clause, $glue);

		return $isMultiDimension ? $clause : "($clause)";
	}

	private function buildSubClause(array $values): string
	{
		$clause = '';
		$glue = ', ';

		foreach ($values as $column => $value) {
			$placeholder = $this->generatePlaceholder($column);
			$clause .= $placeholder . $glue;
			$this->addParameter($placeholder, $value);
		}

		return trim($clause, $glue);
	}

	public function clean(): static
	{
		$this->cleanUp();
		$this->data = [];

		return $this;
	}
}
