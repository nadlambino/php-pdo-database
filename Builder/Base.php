<?php

namespace Inspira\Database\Builder;

use Inspira\Database\Builder\Traits\Helpers;
use PDO;
use PDOStatement;
use Inspira\Database\Builder\Contracts\QueryInterface;
use Symfony\Component\String\Inflector\InflectorInterface;

abstract class Base implements QueryInterface
{
	use Helpers;

	protected readonly string $table;

	protected ?string $tableAlias = null;

	protected ?PDOStatement $statement = null;

	public function __construct(
		protected PDO $connection,
		protected InflectorInterface $inflector,
		?string $table = null
	)
	{
		$this->setTable($table);
	}

	abstract public function toSql(): string;

	public function execute(): bool
	{
		$this->statement = $this->connection->prepare($this->toSql());

		foreach ($this->getParameters() as $key => $value) {
			$this->statement->bindValue($key, $value, $this->type($value));
		}

		return $this->statement->execute();
	}

	protected function setTable(?string $table)
	{
		if (!empty($table)) {
			$this->table = $table;
		}
	}

	abstract public function clean(): static;

	protected function cleanUp()
	{
		$this->statement  = null;
		$this->table      = null;
		$this->tableAlias = null;
		$this->setParameters([]);
	}
}
