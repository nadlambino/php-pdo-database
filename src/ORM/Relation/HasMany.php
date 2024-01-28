<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Inspira\Database\ORM\Model;

class HasMany extends HasRelation
{
	protected ?Model $model;

	public function __construct(Model $model, string|Model $relation, ?string $foreignKey = null, ?string $localKey = null)
	{
		$foreignKey ??= get_short_class_name(get_class($model)) . '_id';
		$foreignKeyValue = is_null($localKey) ? $model->getId() : $model->$localKey;
		$instance = $relation instanceof Model ? $relation : new $relation();
		$this->model = $instance->where($foreignKey, $foreignKeyValue);
		$this->model->$foreignKey = $foreignKeyValue;
	}
}
