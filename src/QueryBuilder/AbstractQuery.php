<?php

namespace Inspira\Database\QueryBuilder;

use Inspira\Container\Container;
use PDO;
use PDOStatement;
use RuntimeException;

abstract class AbstractQuery implements QueryInterface
{
	protected ?PDOStatement $statement = null;

	public function __construct(protected ?PDO $connection)
	{
		$this->connection ??= Container::getInstance()->get(PDO::class);
	}

	public function __toString(): string
	{
		return $this->toSql();
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
		if (empty($this->connection)) {
			throw new RuntimeException('No PDO connection provided.');
		}

		$this->statement = $this->connection->prepare($this->toSql());
		$parameters = $this->parameters ?? [];

		foreach ($parameters as $placeholder => $value) {
			$this->statement->bindValue($placeholder, $value, pdo_type($value));
		}

		return $this->statement->execute();
	}

	protected function clean()
	{
		$this->statement = null;
	}
}
