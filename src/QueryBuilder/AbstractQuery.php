<?php

namespace Inspira\Database\QueryBuilder;

use Inspira\Container\Container;
use PDO;
use PDOStatement;
use RuntimeException;
use Symfony\Component\String\Inflector\InflectorInterface;

abstract class AbstractQuery implements QueryInterface
{
	protected readonly string $table;

	protected ?string $tableAlias = null;

	protected ?PDOStatement $statement = null;

	public function __construct(
		protected ?PDO                $connection,
		protected ?InflectorInterface $inflector,
		?string                       $table = null
	)
	{
		$this->connection ??= Container::getInstance()->get(PDO::class);
		$this->inflector ??= Container::getInstance()->get(InflectorInterface::class);
		$this->setTable($table);
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

	protected function setTable(?string $table)
	{
		if (!empty($table)) {
			$this->table = $table;
		}
	}

	protected function clean()
	{
		$this->statement = null;
		$this->table = null;
		$this->tableAlias = null;
	}
}
