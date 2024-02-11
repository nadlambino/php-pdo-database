<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Relation;

use Closure;
use Inspira\Database\QueryBuilder\RawQuery;
use Inspira\Database\ORM\Model;
use Inspira\Database\ORM\ModelCollection;

/**
 * @method Model distinct()
 * @method Model sum(RawQuery|string $column, ?string $alias = null)
 * @method Model avg(RawQuery|string $column, ?string $alias = null)
 * @method Model min(RawQuery|string $column, ?string $alias = null)
 * @method Model max(RawQuery|string $column, ?string $alias = null)
 * @method Model where(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model orWhere(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model whereLike(string $column, string $value)
 * @method Model whereNotLike(string $column, string $value)
 * @method Model orWhereLike(string $column, string $value)
 * @method Model orWhereNotLike(string $column, string $value)
 * @method Model whereNull(string $column)
 * @method Model whereNotNull(string $column)
 * @method Model orWhereNull(string $column)
 * @method Model orWhereNotNull(string $column)
 * @method Model whereBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method Model whereNotBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method Model orWhereBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method Model orWhereNotBetween(string $column, mixed $lowerBound, mixed $upperBound)
 * @method Model whereIn(string $column, array $values)
 * @method Model whereNotIn(string $column, array $values)
 * @method Model orWhereIn(string $column, array $values)
 * @method Model orWhereNotIn(string $column, array $values)
 * @method Model having(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model orHaving(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model havingNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model orHavingNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model havingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model orHavingNotNull(string|Closure $column, mixed $comparison = null, mixed $value = null)
 * @method Model orderAsc(string $column)
 * @method Model orderDesc(string $column)
 * @method Model groupBy(string $column)
 * @method Model innerJoin(string $table, ?string $alias = null)
 * @method Model leftJoin(string $table, ?string $alias = null)
 * @method Model rightJoin(string $table, ?string $alias = null)
 * @method Model crossJoin(string $table, ?string $alias = null)
 * @method Model on(string $local, string $comparison, ?string $foreign = null)
 * @method Model limit(int $limit)
 * @method Model offset(int $offset)
 * @method Model union(Closure $closure)
 * @method array toArray()
 * @method Model first(...$columns)
 * @method Model last(...$columns)
 * @method Model find(mixed $id)
 * @method ModelCollection get(...$columns)
 * @method Model count(string|RawQuery $column)
 * @method bool update(array $data)
 * @method bool delete()
 * @method bool destroy()
 * @method Model refresh()
 * @method bool save()
 * @method string toSql()
 */
abstract class HasRelation
{
	protected ?Model $model;

	public function __construct(protected Model $foreignModel, protected string|Model $relation, protected ?string $foreignKey = null, protected ?string $localKey = null)
	{
	}

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

	public function getModel(): ?Model
	{
		return $this->model;
	}

	public function getForeignKey(): string
	{
		return $this->foreignKey;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function create(array $data): Model|false
	{
		$foreignKeyValue = $this->foreignModel->{$this->localKey};
		if (!is_null($foreignKeyValue)) {
			$data[$this->getForeignKey()] = $foreignKeyValue;
		}

		return $this->model?->create($data);
	}
}
