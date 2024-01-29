<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Inspira\Database\ORM\Model;

class HasOne extends HasRelation
{
	protected ?Model $model;

	public function __construct(Model $model, string|Model $relation, protected ?string $foreignKey = null, protected ?string $localKey = null)
	{
		$relationModel = $relation instanceof Model ? $relation : new $relation();
		$this->foreignKey ??= get_short_class_name(get_class($model)) . '_id';
		$this->localKey ??= $model->getPk();

		if (!is_null($model->{$this->localKey})) {
			$relationModel = $relationModel->where($this->foreignKey, $model->{$this->localKey});
		}

		$this->model = $relationModel;
	}
}
