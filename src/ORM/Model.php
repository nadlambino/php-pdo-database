<?php

declare(strict_types=1);

namespace Inspira\Database\ORM;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Exception;
use Inspira\Augmentable\Augmentable;
use Inspira\Container\Container;
use Inspira\Contracts\Arrayable;
use Inspira\Database\Builder\Query;
use Inspira\Database\Builder\Raw;
use Inspira\Database\ORM\Traits\ArrayAccessible;
use Inspira\Database\ORM\Traits\IteratorAggregatable;
use Inspira\Database\ORM\Traits\Relations;
use IteratorAggregate;
use PDO;
use ReturnTypeWillChange;
use Symfony\Component\String\Inflector\InflectorInterface;
use Throwable;

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
 * @method softDelete()
 */
abstract class Model implements IteratorAggregate, ArrayAccess, Arrayable
{
	use IteratorAggregatable, ArrayAccessible, Relations, Augmentable {
		Augmentable::__call as augmentCall;
	}

	protected Container $container;

	protected string $table = '';

	protected string $pk = 'id';

	protected string $findBy = '';

	protected ?Query $query = null;

	protected PDO $connection;

	protected InflectorInterface $inflector;

	protected readonly string $model;

	protected array $clauses = [];

	protected array $oldAttributes = [];

	protected array $attributes = [];

	protected const QUERY_METHODS = [
		'distinct', 'count', 'sum', 'avg', 'min',
		'max', 'where', 'orWhere', 'whereLike', 'whereNotLike',
		'orWhereLike', 'orWhereNotLike', 'whereNull', 'whereNotNull', 'orWhereNull',
		'orWhereNotNull', 'whereBetween', 'whereNotBetween', 'orWhereBetween', 'orWhereNotBetween',
		'whereIn', 'whereNotIn', 'orWhereIn', 'orWhereNotIn', 'having',
		'orHaving', 'havingNull', 'orHavingNull', 'havingNotNull', 'orHavingNotNull',
		'orderAsc', 'orderDesc', 'groupBy', 'innerJoin', 'leftJoin',
		'rightJoin', 'crossJoin', 'on', 'limit', 'offset', 'union',
	];

	public function __construct(array $attributes = [])
	{
		$this->container = Container::getInstance();
		$this->findBy = empty($this->findBy) ? $this->pk : $this->findBy;
		$this->setConnection();
		$this->setInflector();
		$this->setModel();
		$this->setTable();
		$this->setQuery();
		$this->setSofDelete();
		$this->attributes = empty($attributes) ? $this->attributes : $attributes;
	}

	public function getPk(): string
	{
		return $this->pk;
	}

	/**
	 * Set properties of table columns
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $name, mixed $value): void
	{
		$this[$name] = $value;
	}

	/**
	 * Access table columns via property access
	 *
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get(string $name): mixed
	{
		try {
			return $this[$name];
		} catch (Throwable) {
			throw new Exception("Property `$name` does not exist on model `$this->model`.");
		}
	}

	public function __call(string $method, array $arguments)
	{
		if ($this->augmented($method)) {
			return $this->augmentCall($method, $arguments);
		}

		// If method doesn't exist in query object, and it is one of the available methods,
		// We will compile them into the `$clauses` array for later use in the query builder
		// Then on our query methods (get, first, last, update, and delete),
		// We will loop through these `$clauses` and append them in the query builder
		if (in_array($method, self::QUERY_METHODS)) {
			$self = clone $this;
			$self->addQueryClause($method, $arguments);

			return $self;
		}

		if (!method_exists($this->query, $method)) {
			throw new BadMethodCallException("Call to undefined method: `$method`");
		}

		return $this->query->$method(...$arguments);
	}

	public function get(...$columns): ModelCollection
	{
		$query = $this->query->select(...$columns);
		$this->attachClauses($query);

		$models = $query->get();
		$this->attachRelations($models);

		return new ModelCollection($models, $this->model);
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param ...$columns
	 * @return $this|null
	 */
	public function first(...$columns): ?static
	{
		$query = $this->query->select(...$columns);
		$this->attachClauses($query);

		$model = $query->limit(1)->first();
		$this->attachRelations($model);

		return $model;
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param ...$columns
	 * @return $this|null
	 */
	public function last(...$columns): ?static
	{
		$query = $this->query->select(...$columns);
		$this->attachClauses($query);

		$model = $query->orderDesc($this->pk)->limit(1)->first();
		$this->attachRelations($model);

		return $model;
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param mixed $id
	 * @return $this|null
	 */
	public function find(mixed $id): ?static
	{
		$query = $this->query->select()->where($this->findBy, $id);
		$this->attachClauses($query);

		$model = $query->first();
		$this->attachRelations($model);

		return $model;
	}

	/**
	 * Update or create a new model
	 * Note: When updating a user using this approach, it won't store the old values to the oldAttributes property
	 * If you need to have a reference to the old values, consider using the `update` method.
	 *
	 * @return bool
	 */
	public function save(): bool
	{
		$attributes = $this->toArray();

		if ($this->hasId()) {
			return $this->where($this->pk, $this->getId())->update($attributes);
		}

		return $this->create($attributes);
	}

	/**
	 * Create one or many data
	 * $data could be a single or multidimensional array of column => value pairs
	 *
	 * @param array $data
	 * @return bool
	 */
	public function create(array $data): bool
	{
		$created = $this->query->insert($data)->execute();

		if ($created) {
			$this->{$this->pk} = $this->connection->lastInsertId();
			$this->oldAttributes = $data;
		}

		return $created;
	}

	/**
	 * Update a models combined with where clause
	 * Note: When calling the `update` method on an active model, it will update the active model
	 * When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param array $data
	 * @return bool
	 */
	public function update(array $data): bool
	{
		$oldAttributes = $this->toArray();
		$query = $this->query->update($this->table)->set($data);
		$query = $this->hasId() && $this->isQueryNotModified() ? $query->where($this->pk, $this->getId()) : $query;
		$this->attachClauses($query);

		$updated = $query->execute();

		if ($updated) {
			$this->oldAttributes = $oldAttributes;
		}

		return $updated;
	}

	/**
	 * Delete a models combined with where clause
	 * Note: When calling the `delete` method on an active model, it will delete the active model
	 * When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		// If the model has ID, and it has no chained queries upon calling this method
		// Then we can just redirect the call to the `destroy` method
		if ($this->hasId() && $this->isQueryNotModified()) {
			return $this->destroy();
		}

		// If the model doesn't have ID, or it has chained queries, we will create a new query
		$query = $this->isSoftDeletable()
			? $this->softDelete()
			: $this->query->delete($this->table);

		$this->attachClauses($query);

		return $query->execute();
	}

	/**
	 * Delete the current model in the database
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		if ($this->hasId()) {
			$query = $this->isSoftDeletable()
				? $this->softDelete()
				: $this->query->delete($this->table);

			return $query->where($this->pk, $this->getId())->execute();
		}

		return false;
	}

	public function refresh(): static
	{
		$attributes = $this->toArray();

		if (empty($attributes)) {
			return $this;
		}

		return $this->find($this->getId());
	}

	/**
	 * Override ArrayObject count method to redirect the call to query object
	 *
	 * @param Raw|string|null $column
	 * @param string|null $alias
	 * @return static
	 */
	#[ReturnTypeWillChange]
	public function count(Raw|string|null $column = null, ?string $alias = null): static
	{
		$this->addQueryClause(__FUNCTION__, func_get_args());

		return $this;
	}

	public function toArray(): array
	{
		$modelArray = iterator_to_array($this);

		foreach ($modelArray as $attribute => $value) {
			if ($value instanceof Arrayable) {
				$modelArray[$attribute] = $value->toArray();
			} else if (is_iterable($value)) {
				$modelArray[$attribute] = iterator_to_array($value);
			}
		}

		return $modelArray;
	}

	protected function hasId(): bool
	{
		$attributes = $this->toArray();

		return isset($attributes[$this->pk]) && !is_null($attributes[$this->pk]);
	}

	public function getId(): mixed
	{
		return $this->hasId() ? $this->{$this->pk} : null;
	}

	protected function isQueryNotModified(): bool
	{
		$countClauses = count($this->clauses);
		// If the model is soft deletable and the clauses count is 1
		// It means there are no chained queries upon calling this method
		$softDeletableNoAddedQuery = $this->isSoftDeletable() && $countClauses === 1;

		// If the model is non-soft deletable and clauses count is 0
		// It means there are no chained queries upon calling this method
		$nonSoftDeletableNoAddedQuery = !$this->isSoftDeletable() && $countClauses === 0;

		return $softDeletableNoAddedQuery || $nonSoftDeletableNoAddedQuery;
	}

	/**
	 * @return void
	 * @throws
	 */
	protected function setConnection(): void
	{
		$this->connection ??= $this->container->make(PDO::class);
	}

	/**
	 * @throws
	 */
	protected function setInflector()
	{
		$this->inflector ??= $this->container->make(InflectorInterface::class);
	}

	private function setModel()
	{
		$this->model = get_called_class();
	}

	private function setTable()
	{
		$class = get_short_class_name($this->model);
		$class = $this->inflector->pluralize($class)[0] ?? $class;
		$this->table = empty($this->table) ? strtolower($class) : $this->table;
	}

	private function setQuery()
	{
		$this->query = (new Query($this->connection, $this->inflector))
			->model($this->model)
			->table($this->table);
	}

	protected function addQueryClause(string $name, array $arguments): void
	{
		$this->clauses[] = compact('name', 'arguments');
	}

	protected function removeQueryClause(string $name): void
	{
		
	}

	/**
	 * Append the clauses to the query object
	 *
	 * @param mixed $query
	 * @return void
	 */
	private function attachClauses(mixed $query): void
	{
		foreach ($this->clauses as $clause) {
			$method = $clause['name'];
			$arguments = $clause['arguments'];

			$query->$method(...$arguments);
		}
	}

	private function isSoftDeletable(): bool
	{
		return method_exists($this, 'softDelete') && property_exists($this, 'deletedAt');
	}

	private function setSofDelete()
	{
		if ($this->isSoftDeletable()) {
			$this->addQueryClause('whereNull', [$this->deletedAt]);
		}
	}
}
