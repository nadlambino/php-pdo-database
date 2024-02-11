<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

use Inspira\Database\QueryBuilder\Traits\Helpers;
use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

class Query
{
	private ?string $table = null;

	private ?string $model = null;

	public function __construct(protected PDO $connection, protected InflectorInterface $inflector)
	{
	}

	public function table(string $table): static
	{
		$this->table = $table;

		return $this;
	}

	public function model(string $model): static
	{
		$this->model = $model;

		return $this;
	}

	public function select(...$columns): SelectQuery
	{
		return (new SelectQuery($columns, $this->connection, $this->inflector, $this->model))
			->from($this->table ?? '');
	}

	public function insert(array $data): InsertQuery
	{
		return (new InsertQuery($data, $this->connection, $this->inflector))
			->into($this->table ?? '');
	}

	public function update(string $table): UpdateQuery
	{
		return new UpdateQuery($this->connection, $this->inflector, $this->table ?? $table);
	}

	public function delete(string $table): Delete
	{
		return new Delete($this->connection, $this->inflector, $this->table ?? $table);
	}

	/**
	 * Create a raw SQL statement
	 *
	 * @param string $sql
	 * @param array $parameters
	 * @return Sql
	 */
	public function raw(string $sql, array $parameters = []): Sql
	{
		return new Sql($this->connection, $sql, $parameters);
	}
}
