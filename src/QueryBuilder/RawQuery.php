<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use PDO;
use PDOStatement;

class RawQuery extends AbstractQuery
{
	protected ?PDOStatement $statement = null;

	public function __construct(
		protected string $sql,
		protected array  $parameters = [],
		protected ?PDO   $connection = null,
	)
	{
		parent::__construct($this->connection, null);
	}

	public function toSql(): string
	{
		return $this->sql;
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
