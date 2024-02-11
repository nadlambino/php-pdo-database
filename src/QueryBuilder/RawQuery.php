<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use PDO;
use PDOStatement;
use RuntimeException;

class RawQuery implements QueryInterface, RawQueryInterface
{
	protected ?PDOStatement $statement = null;

	public function __construct(
		protected string $sql,
		protected array  $parameters = [],
		protected ?PDO   $connection = null,
	)
	{
	}

	public function __toString(): string
	{
		return $this->toSql();
	}

	public function execute(): bool
	{
		if (empty($this->connection)) {
			throw new RuntimeException('No PDO connection provided.');
		}

		$this->statement = $this->connection->prepare($this->toSql());

		foreach ($this->parameters as $placeholder => $value) {
			$this->statement->bindValue($placeholder, $value, pdo_type($value));
		}

		return $this->statement->execute();
	}

	public function toSql(): string
	{
		return $this->sql;
	}

	public function toRawSql(): string
	{
		return str_replace(array_keys($this->parameters), array_values($this->parameters), $this->sql);
	}

	public function get($fetchMode = PDO::FETCH_ASSOC): array|false|null
	{
		$this->execute();
		$this->statement?->setFetchMode($fetchMode);

		return $this->statement?->fetchAll();
	}

	public function first($fetchMode = PDO::FETCH_ASSOC): mixed
	{
		$this->execute();
		$this->statement?->setFetchMode($fetchMode);

		return $this->statement?->fetch();
	}
}