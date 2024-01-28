<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use ArrayIterator;
use Traversable;

/**
 * @property $attributes
 */
trait IteratorAggregatable
{
	/**
	 * Gets an iterator for the collection.
	 *
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->attributes);
	}
}
