<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

/**
 * @property $attributes
 */
trait ArrayAccessible
{
	/**
	 * Checks if an offset exists in the collection.
	 *
	 * @param mixed $offset The offset to check.
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->attributes[$offset]);
	}

	/**
	 * Gets the item at the specified offset.
	 *
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->attributes[$offset] ?? null;
	}

	/**
	 * Sets the value at the specified offset.
	 *
	 * @param mixed $offset The offset to set.
	 * @param mixed $value The value to set.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (empty($offset)) {
			return;
		}

		if (in_array($offset, $this->hidden)) {
			return;
		}

		$this->attributes[$offset] = $value;
	}

	/**
	 * Unsets the item at the specified offset.
	 *
	 * @param mixed $offset The offset to unset.
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->attributes[$offset]);
	}
}
