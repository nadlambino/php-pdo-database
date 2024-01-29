<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Closure;
use Inspira\Database\Builder\Raw;

/**
 * @method self distinct()
 * @method self sum(Raw|string $column, ?string $alias = null)
 * @method self avg(Raw|string $column, ?string $alias = null)
 * @method self min(Raw|string $column, ?string $alias = null)
 * @method self max(Raw|string $column, ?string $alias = null)
 * @method self where(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self orWhere(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self whereLike(string $column, string $value)
 * @method self whereNotLike(string $column, string $value)
 * @method self orWhereLike(string $column, string $value)
 * @method self orWhereNotLike(string $column, string $value)
 * @method self whereNull(string $column)
 * @method self whereNotNull(string $column)
 * @method self orWhereNull(string $column)
 * @method self orWhereNotNull(string $column)
 * @method self whereBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method self whereNotBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method self orWhereBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method self orWhereNotBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method self whereIn(string $column, array $values)
 * @method self whereNotIn(string $column, array $values)
 * @method self orWhereIn(string $column, array $values)
 * @method self orWhereNotIn(string $column, array $values)
 * @method self having(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self orHaving(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self havingNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self orHavingNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self havingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self orHavingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method self orderAsc(string $column)
 * @method self orderDesc(string $column)
 * @method self groupBy(string $column)
 * @method self innerJoin(string $table, ?string $alias = null)
 * @method self leftJoin(string $table, ?string $alias = null)
 * @method self rightJoin(string $table, ?string $alias = null)
 * @method self crossJoin(string $table, ?string $alias = null)
 * @method self on(string $local, string $comparison, ?string $foreign = null)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @method self union(Closure $closure)
 * @method array toArray()
 * @method self first(...$columns)
 * @method self last(...$columns)
 * @method self find(mixed $id)
 * @method array get(...$columns)
 * @method self count(string|Raw $column)
 * @method bool create(array $data)
 * @method bool update(array $data)
 * @method bool delete()
 * @method bool destroy()
 * @method self refresh()
 * @method bool save()
 */
abstract class HasRelation
{
	public function __call(string $name, array $arguments)
	{
		return $this->model?->$name(...$arguments);
	}

	public function __set(string $name, $value): void
	{
		$this->model->$name = $value;
	}

	public function __get(string $name)
	{
		return $this->model?->$name;
	}

	public function getForeignKey(): string
	{
		return $this->foreignKey;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}
}
