<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\ModelCollection;
use Inspira\Database\ORM\Relation\HasMany;
use Inspira\Database\ORM\Relation\HasOne;

trait Relations
{
	protected array $relations = [];

	public function with(string $relation): static
	{
		// If the current model already has an ID, we will just call the relationship method
		// Then assign the return value to the property with the same name as the method
		if ($this->hasId()) {
			$model = $this->$relation();
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

		$ids = is_array($models)
			? array_map(fn ($model) =>$model->{$model->getPk()}, $models)
			: [$models->{$models->getPk()}];

		foreach ($this->relations as $relation) {
			$this->resolveRelation($this, $relation, $models, $ids);
		}

	}

	/**
	 * Resolve the relation method based on its type
	 *
	 * @param Model $model
	 * @param string $method
	 * @param Model|array $models
	 * @param array $ids
	 * @return Model
	 */
	private function resolveRelation(Model $model, string $method, Model|array $models, array $ids = []): Model
	{
		/** @var HasOne|HasMany $relation */
		$relation = $model->$method();
		$response = $this->resolveMethod($relation, $ids);

		if (is_array($models)) {
			foreach ($models as $model) {
				if ($response instanceof Model && $model->{$relation->getLocalKey()} === $response->{$relation->getForeignKey()}) {
					$model->$method = $response;
				}

				if ($response instanceof ModelCollection) {
					if ($relation instanceof HasOne) {
						$model->$method = $response->where($relation->getForeignKey(), $model->{$relation->getLocalKey()})->first();
					}

					if ($relation instanceof HasMany) {
						$model->$method = $response->where($relation->getForeignKey(), $model->{$relation->getLocalKey()});
					}
				}

				$model->relations = array_unique($this->relations);
			}
		} else {
			$models->$method = $relation instanceof HasOne ? $response->first() : $response;
			$models->relations = array_unique($this->relations);
		}

		return $model;
	}

	private function resolveMethod(HasOne|HasMany $model, array $ids = []): mixed
	{
		if ($model instanceof HasOne) {
			if (!empty($ids)) {
				$response = $model->whereIn($model->getForeignKey(), $ids)->get();
			} else {
				$response = $model->first();
			}
		} else {
			if (!empty($ids)) {
				$response = $model->whereIn($model->getForeignKey(), $ids)->get();
			} else {
				$response = $model->get();
			}
		}

		return $response;
	}
}
