<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\Relation\HasRelation;

trait Query
{
	public function whereHas(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null): static
	{
		return $this->whereExists($modelOrRelation, $foreignColumn, $localColumn);
	}

	public function whereDoesntHave(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null): static
	{
		return $this->whereExists($modelOrRelation, $foreignColumn, $localColumn, false);
	}

	public function withHas(string $relation): static
	{
		return $this->with($relation)->whereHas($relation);
	}

	protected function whereExists(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null, bool $exists = true): static
	{
		$method = $exists ? 'whereHas' : 'whereDoesntHave';

		// Handles model
		if ($modelOrRelation instanceof Model) {
			$query = $modelOrRelation->query->select();
			$modelOrRelation->attachClauses($query);
			$this->addQueryClause($method, [$query]);

			return $this;
		}

		// Handles model from relation
		if (method_exists($this, $modelOrRelation)) {
			/** @var HasRelation $relation */
			$relation = $this->$modelOrRelation();
			$foreignTable = query_quote($this->connection, $this->table);
			$table = query_quote($this->connection, $relation->getModel()->table);
			$foreignColumn ??= query_quote($this->connection, $this->inflector->singularize($this->table)[0] . '_id');
			$localColumn ??= query_quote($this->connection, $this->pk);
			$modelOrRelation = $relation->getModel()->whereRaw("$table.$foreignColumn = $foreignTable.$localColumn");
			$query = $modelOrRelation->query->select();
			$modelOrRelation->attachClauses($query);
			$this->addQueryClause($method, [$query]);

			return $this;
		}

		$table = !class_exists($modelOrRelation) ? $modelOrRelation : (new $modelOrRelation())->table;
		$this->addQueryClause($method, [$table, $foreignColumn, $localColumn]);

		return $this;
	}
}
