<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Inspira\Database\ORM\Model;

class HasOne extends HasRelation
{
	protected ?Model $model;

	public function __construct(Model $model, string|Model $relation, ?string $foreignKey = null, ?string $localKey = null)
	{
		$relationModel = $relation instanceof Model ? $relation : new $relation();
		$foreignKey ??= get_short_class_name(get_class($model)) . '_id';
		$localKey ??= $model->getPk();

		$this->model = $relationModel->where($foreignKey, $model->$localKey)->first();
	}
}
