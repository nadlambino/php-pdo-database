<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\Relation\HasRelation;

trait Query
{
	public function whereExists(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null): static
	{
		return $this->handleWhereExists($modelOrRelation, $foreignColumn, $localColumn);
	}

	public function whereNotExists(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null): static
	{
		return $this->handleWhereExists($modelOrRelation, $foreignColumn, $localColumn, false);
	}

	public function withExisting(string $relation): static
	{
		return $this->with($relation)->whereExists($relation);
	}

	protected function handleWhereExists(Model|string $modelOrRelation, ?string $foreignColumn = null, ?string $localColumn = null, bool $exists = true): static
	{
		$method = $exists ? 'whereExists' : 'whereNotExists';

		// Handles model
		if ($modelOrRelation instanceof Model) {
			$query = $modelOrRelation->query->select();
			$modelOrRelation->attachQueries($query);
			$this->addQuery($method, [$query]);

			return $this;
		}

		// Handles model from relation
		if (method_exists($this, $modelOrRelation)) {
			/** @var HasRelation $relation */
			$relation = $this->$modelOrRelation();
			$foreignTable = pdo_quote($this->table);
			$table = pdo_quote($relation->getModel()->table);
			$foreignColumn ??= pdo_quote($this->inflector->singularize($this->table)[0] . '_id');
			$localColumn ??= pdo_quote($this->pk);
			$modelOrRelation = $relation->getModel()->whereRaw("$table.$foreignColumn = $foreignTable.$localColumn");
			$query = $modelOrRelation->query->select();
			$modelOrRelation->attachQueries($query);
			$this->addQuery($method, [$query]);

			return $this;
		}

		$table = class_exists($modelOrRelation) ? (new $modelOrRelation())->table : $modelOrRelation;
		$this->addQuery($method, [$table, $foreignColumn, $localColumn]);

		return $this;
	}
}
