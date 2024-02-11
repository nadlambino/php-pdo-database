<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder\Traits;

use Inspira\Database\QueryBuilder\Enums\Reserved;

/**
 * The convention we follow here is that the left table should be the one that holds the reference key
 * E.g. `tasks` table holds the user_id column which is a reference to a user in the `users` table
 * The reference column should be in the following format; {singular form of table name}_id
 * And the format of the column name from the referenced table should be `id`
 * Following this will make the use of the on() method just an optional
 *
 * Note: This is not a strict convention, and you are still allowed to have custom key names
 * For that matter, you can use the on() method to manually set the reference of joining tables
 */
trait Join
{
	protected array $joins = [];

	public function innerJoin(string $table, ?string $alias = null): static
	{
		return $this->addJoin(Reserved::INNER_JOIN->value, $table, $alias);
	}

	public function leftJoin(string $table, ?string $alias = null): static
	{
		return $this->addJoin(Reserved::LEFT_JOIN->value, $table, $alias);
	}

	public function rightJoin(string $table, ?string $alias = null): static
	{
		return $this->addJoin(Reserved::RIGHT_JOIN->value, $table, $alias);
	}

	public function crossJoin(string $table, ?string $alias = null): static
	{
		return $this->addJoin(Reserved::CROSS_JOIN->value, $table, $alias);
	}

	/**
	 * This can be used to manually set the reference of joining tables
	 * Use when the left table is not the one that holds the reference key
	 * Or when the keys don't follow the convention we used by default
	 * This will be applied on the last joined table overriding the defaults
	 *
	 * @param string $local
	 * @param string $comparison
	 * @param string|null $foreign
	 * @return $this
	 */
	public function on(string $local, string $comparison, ?string $foreign = null): static
	{
		$lastJoinIndex = count($this->joins) - 1;
		$lastJoin = $this->joins[$lastJoinIndex];
		$lastJoin['local'] = $this->getFormattedColumn($local);
		$lastJoin['foreign'] = $this->getFormattedColumn($foreign ?? $comparison);
		$lastJoin['comparison'] = isset($foreign) ? $comparison : '=';
		$this->joins[$lastJoinIndex] = $lastJoin;

		return $this;
	}

	protected function addJoin(string $type, string $table, ?string $alias = null, string $comparison = '='): static
	{
		$foreignTable = singularize($this->table);
		$rawTable = $alias ?? $table;
		$local = $this->getFormattedColumn("$this->table.id");
		$foreign = $this->getFormattedColumn("$rawTable.{$foreignTable}_id");
		$table = pdo_quote($table);
		$alias = isset($alias) ? pdo_quote($alias) : null;

		if ($table === Reserved::CROSS_JOIN->value) {
			$local = null;
			$foreign = null;
			$comparison = null;
		}
		$this->joins[] = compact('table', 'alias', 'local', 'foreign', 'comparison', 'type');

		return $this;
	}

	protected function getJoinClause(): string
	{
		$clause = '';

		foreach ($this->joins as $join) {
			if ($join['type'] === Reserved::CROSS_JOIN->value) {
				$clause .= ' ' . implode(' ', [$join['type'], $join['table'], $join['alias']]);
				continue;
			}

			$clause .= ' ' . implode(' ', [$join['type'], $join['table'], $join['alias'], Reserved::ON->value, $join['local'], $join['comparison'], $join['foreign']]);
		}

		return $clause;
	}
}
