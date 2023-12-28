<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\Relation\HasOne;
use Inspira\Database\ORM\Relation\HasMany;

trait Relations
{
	protected array $relations = [];

	public function with(string $relation): static
	{
		// If the current model already has an ID, we will just call the relationship method
		// Then assign the return value to the property with the same name as the method
		if ($this->hasId()) {
			$model           = $this->$relation();
			$this->$relation = $this->resolveMethod($model);

			return $this;
		}

		// If the model doesn't have an ID yet, we will just store the relationship method name
		// Then we will call these methods later to attach the response to this model instance
		// See the `attachRelations` method
		$this->relations[] = $relation;

		return $this;
	}

	public function hasOne(string|Model $model, ?string $foreignKey = null, ?string $localKey = null): HasOne
	{
		return new HasOne($this, $model, $foreignKey, $localKey);
	}

	public function hasMany(string|Model $model, ?string $foreignKey = null, ?string $localKey = null): HasMany
	{
		return new HasMany($this, $model, $foreignKey, $localKey);
	}

	/**
	 * Accepts the current model instance then attach the response of relation methods
	 *
	 * @param Model|array|null $models
	 * @return void
	 */
	protected function attachRelations(Model|array|null &$models): void
	{
		if (is_null($models)) {
			return;
		}

		$isArray = is_array($models);

		foreach ($this->relations as $relation) {
			if ($isArray) {
				$models = array_map(fn($model) => $this->resolveRelation($model, $relation), $models);
			} else {
				$models = $this->resolveRelation($models, $relation);
			}
		}
	}

	/**
	 * Resolve the relation method based on its type
	 *
	 * @param Model $model
	 * @param string $method
	 * @return Model
	 */
	private function resolveRelation(Model $model, string $method): Model
	{
		$relation = $model->$method();
		$model->$method   = $this->resolveMethod($relation);
		$model->relations = array_unique($this->relations);

		return $model;
	}

	private function resolveMethod(mixed $model): mixed
	{
		return match (true) {
			$model instanceof HasOne  => $model?->toArray(),
			$model instanceof HasMany => $model->get(),
			default                   => $model
		};
	}
}
