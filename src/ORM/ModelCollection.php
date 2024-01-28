<?php

declare(strict_types=1);

namespace Inspira\Database\ORM;

use Inspira\Collection\Collection;
use Inspira\Collection\Contracts\CollectionInterface;

class ModelCollection extends Collection
{
	public function __construct(CollectionInterface|array $items = [], string $type = Model::class)
	{
		parent::__construct($items, $type);
	}
}
