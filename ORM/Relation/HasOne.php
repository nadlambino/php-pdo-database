<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\Traits\Helpers;

class HasOne extends HasRelation
{
	use Helpers;

	protected ?Model $model;

	public function __construct(Model $model, string|Model $relation, ?string $foreignKey = null, ?string $localKey = null)
	{
		$name         = $relation instanceof Model ? get_class($relation) : $relation;
		$instance     = $relation instanceof Model ? $relation : new $relation();
		$foreignKey ??= $this->getShortClassName($name) . '_id';

		$this->model = is_null($localKey)
			? $instance->find($model->$foreignKey)
			: $instance->where($localKey, $model->$foreignKey)->first();
	}
}
