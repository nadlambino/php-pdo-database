<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

use Inspira\Container\Container;

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
	 * If offset is empty, push the value to attributes.
	 *
	 * @param mixed $offset The offset to set.
	 * @param mixed $value The value to set.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (empty($offset)) {
			$this->attributes[] = $value;
			return;
		}

		if (in_array($offset, $this->hidden)) {
			return;
		}

		$value = $this->cast($offset, $value);

		$old = $this->attributes;
		$old[$offset] ??= $value;
		$this->attributes[$offset] = $value;
		$this->oldAttributes = [...$old, ...$this->oldAttributes];
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

	private function cast(mixed $attribute, mixed $initialValue): mixed
	{
		if (!isset($this->casts[$attribute])) {
			return $initialValue;
		}

		$type = $this->casts[$attribute];

		return match (true) {
			class_exists($type) => new $type($initialValue),
			Container::getInstance()->has($type) => new (Container::getInstance()->make($type))($initialValue),
			default => set_type($initialValue, $type)
		};
	}
}
