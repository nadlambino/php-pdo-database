<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Inspira\Database\ORM\Model;

class HasOne extends HasRelation
{
	public function __construct(protected Model $foreignModel, protected string|Model $relation, protected ?string $foreignKey = null, protected ?string $localKey = null)
	{
		$this->relation = $relation instanceof Model ? $relation : new $relation();
		$this->foreignKey ??= class_basename(get_class($foreignModel)) . '_id';
		$this->localKey ??= $foreignModel->getPk();

		if (!is_null($foreignModel->{$this->localKey})) {
			$this->relation = $this->relation->where($this->foreignKey, $foreignModel->{$this->localKey});
		}

		$this->model = $this->relation;

		parent::__construct($this->foreignModel, $this->relation, $this->foreignKey, $this->localKey);
	}
}
