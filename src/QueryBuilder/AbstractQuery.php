<?php

namespace Inspira\Database\QueryBuilder;

use Inspira\Database\QueryBuilder\Traits\Helpers;
use PDO;
use PDOStatement;
use Symfony\Component\String\Inflector\InflectorInterface;

abstract class AbstractQuery implements QueryInterface
{
	use Helpers;

	protected readonly string $table;

	protected ?string $tableAlias = null;

	protected ?PDOStatement $statement = null;

	public function __construct(
		protected PDO                $connection,
		protected InflectorInterface $inflector,
		?string                      $table = null
	)
	{
		$this->setTable($table);
	}

	abstract public function toSql(): string;

	public function toRawSql(): string
	{
		$sql = $this->toSql();
		$parameters = array_map(function ($parameter) {
			return var_export($parameter, true);
		}, $this->parameters ?? []);

		return str_replace(array_keys($parameters), array_values($parameters), $sql);
	}

	public function execute(): bool
	{
		$this->statement = $this->connection->prepare($this->toSql());
		$parameters = $this->getParameters();

		foreach ($parameters as $placeholder => $value) {
			$this->statement->bindValue($placeholder, $value, pdo_type($value));
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
		$this->statement = null;
		$this->table = null;
		$this->tableAlias = null;
		$this->setParameters([]);
	}
}
