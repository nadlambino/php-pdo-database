<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

class Query
{
	private ?Raw $raw;

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

	public function select(...$columns): Select
	{
		return (new Select($columns, $this->connection, $this->inflector, $this->model))
			->from($this->table ?? '');
	}

	public function insert(array $data): Insert
	{
		return (new Insert($data, $this->connection, $this->inflector))
			->into($this->table ?? '');
	}

	public function update(string $table): Update
	{
		return new Update($this->connection, $this->inflector, $this->table ?? $table);
	}

	public function delete(string $table): Delete
	{
		return new Delete($this->connection, $this->inflector, $this->table ?? $table);
	}

	/**
	 * Pass a raw query which will then be directly used
	 * Make sure that you sanitized what you pass here
	 *
	 * @param string $query
	 * @return Raw
	 */
	public function raw(string $query): Raw
	{
		if (isset($this->raw)) {
			return $this->raw->query($query);
		}

		$this->raw = new Raw();
		$this->raw->query($query);

		return $this->raw;
	}
}
