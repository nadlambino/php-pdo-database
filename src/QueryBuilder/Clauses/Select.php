<?php

namespace Inspira\Database\QueryBuilder\Clauses;

use Inspira\Database\QueryBuilder\SelectQuery as SelectBuilder;
use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * The purpose of this class is to be use as a wrapper for the Inspira\Database\Builder\Select class
 * This is the one that will be pass to a closure that needs the Inspira\Database\Builder\Select class
 * This ensures that the closure will receive the correct builder with only the methods that it needs
 */
class Select
{
	private ?SelectBuilder $select = null;

	public function __construct(protected PDO $connection, protected InflectorInterface $inflector)
	{
	}

	public function select(...$columns): SelectBuilder
	{
		if (isset($this->select)) {
			return $this->select;
		}

		$this->select = new SelectBuilder($columns, $this->connection, $this->inflector);

		return $this->select;
	}

	public function toSql(): string
	{
		return $this->select?->toSql();
	}

	public function getParameters(): array
	{
		return $this->select?->getParameters() ?? [];
	}
}
