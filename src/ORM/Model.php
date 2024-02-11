<?php

declare(strict_types=1);

namespace Inspira\Database\ORM;

use ArrayAccess;
use Closure;
use DateTime;
use Exception;
use Inspira\Augmentable\Augmentable;
use Inspira\Container\Container;
use Inspira\Contracts\Arrayable;
use Inspira\Database\QueryBuilder\Delete;
use Inspira\Database\QueryBuilder\InsertQuery;
use Inspira\Database\QueryBuilder\Query;
use Inspira\Database\QueryBuilder\SelectQuery;
use Inspira\Database\QueryBuilder\UpdateQuery;
use Inspira\Database\Exceptions\BadMethodCallException;
use Inspira\Database\ORM\Traits\Aggregates;
use Inspira\Database\ORM\Traits\ArrayAccessible;
use Inspira\Database\ORM\Traits\IteratorAggregatable;
use Inspira\Database\ORM\Traits\Query as QueryTrait;
use Inspira\Database\ORM\Traits\Relations;
use InvalidArgumentException;
use IteratorAggregate;
use PDO;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * @method self distinct()
 * @method self whereRaw(string $query)
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
 * @method UpdateQuery softDelete()
 */
abstract class Model implements IteratorAggregate, ArrayAccess, Arrayable
{
	use IteratorAggregatable, ArrayAccessible, Relations, QueryTrait, Aggregates, Augmentable {
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

	protected array $queries = [];

	protected array $oldAttributes = [];

	protected array $attributes = [];

	protected array $hidden = [];

	protected array $casts = [];

	protected const CREATED_AT = 'created_at';

	protected const UPDATED_AT = 'updated_at';

	protected const DELETED_AT = 'deleted_at';

	protected const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	protected const QUERY_METHODS = [
		'distinct', 'where', 'orWhere', 'whereLike', 'whereNotLike',
		'orWhereLike', 'orWhereNotLike', 'whereNull', 'whereNotNull', 'orWhereNull',
		'orWhereNotNull', 'whereBetween', 'whereNotBetween', 'orWhereBetween', 'orWhereNotBetween',
		'whereIn', 'whereNotIn', 'orWhereIn', 'orWhereNotIn', 'having',
		'orHaving', 'havingNull', 'orHavingNull', 'havingNotNull', 'orHavingNotNull',
		'orderAsc', 'orderDesc', 'groupBy', 'innerJoin', 'leftJoin',
		'rightJoin', 'crossJoin', 'on', 'limit', 'offset', 'union', 'whereRaw'
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
		$this->setAttributes($attributes);
	}

	public function setAttribute(string $column, mixed $value)
	{
		$value = $this->cast($column, $value);
		$this->attributes[$column] = $value;
	}

	private function setAttributes(array $attributes)
	{
		foreach ($attributes as $column => $value) {
			$value = $this->cast($column, $value);
			$this->setAttribute($column, $value);
		}
	}

	private function castData(array $data): array
	{
		$casted = [];

		foreach ($data as $column => $value) {
			$value = $this->cast($column, $value);
			$casted[$column] = $value;
		}

		return $casted;
	}

	private function removeHiddenAttributes()
	{
		foreach ($this->attributes as $column => $value) {
			if (in_array($column, $this->hidden)) {
				unset($this->attributes[$column]);
			}
		}
	}

	private function removeHiddenOldAttributes()
	{
		foreach ($this->oldAttributes as $column => $value) {
			if (in_array($column, $this->hidden)) {
				unset($this->oldAttributes[$column]);
			}
		}
	}

	private function cast(mixed $column, mixed $initialValue): mixed
	{
		if (!isset($this->casts[$column])) {
			return $initialValue;
		}

		$type = $this->casts[$column];

		return match (true) {
			in_array($type, ['bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'array', 'object', 'null']) => set_type($initialValue, $type),
			is_callable($type) => $type($initialValue),
			class_exists($type) => new $type($initialValue),
			Container::getInstance()->has($type) => new (Container::getInstance()->make($type))($initialValue),
			default => $initialValue
		};
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
		return $this[$name];
	}

	/**
	 * Create a new instance of the model and call the method from the instance
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $arguments): mixed
	{
		return (new static())->$method(...$arguments);
	}

	public function __call(string $method, array $arguments)
	{
		if ($this->augmented($method)) {
			return $this->augmentCall($method, $arguments);
		}

		// If method doesn't exist in query object, and it is one of the available methods,
		// We will compile them into the `$queries` array for later use in the query builder
		// Then on our query methods (get, first, last, update, and delete),
		// We will loop through these `$queries` and append them in the query builder
		if (in_array($method, self::QUERY_METHODS)) {
			$self = clone $this;
			$self->addQuery($method, $arguments);

			return $self;
		}

		if (!method_exists($this->query, $method)) {
			throw new BadMethodCallException(method: $method);
		}

		return $this->query->$method(...$arguments);
	}

	public function get(...$columns): ModelCollection
	{
		$query = $this->query->select(...$columns);
		$this->attachQueries($query);

		$models = $query->get();
		$this->attachRelations($models);

		return new ModelCollection($models, $this->model);
	}

	public function getArray(...$columns): array
	{
		return $this->get(...$columns)->toArray();
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param ...$columns
	 * @return static|null
	 */
	public function first(...$columns): ?static
	{
		$query = $this->query->select(...$columns);
		$this->attachQueries($query);

		$model = $query->limit(1)->first();
		$this->attachRelations($model);

		return $model;
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param ...$columns
	 * @return static|null
	 */
	public function last(...$columns): ?static
	{
		$query = $this->query->select(...$columns);
		$this->attachQueries($query);

		$model = $query->orderDesc($this->pk)->limit(1)->first();
		$this->attachRelations($model);

		return $model;
	}

	/**
	 * Note: When chained with query methods, it will create a new query and the active model won't be affected
	 *
	 * @param mixed $id
	 * @return static|null
	 */
	public function find(mixed $id): ?static
	{
		$query = $this->query->select()->where($this->findBy, $id);
		$this->attachQueries($query);

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
			return $this->update($attributes);
		}

		return $this->create($attributes) !== false;
	}

	/**
	 * Create one or many data
	 * $data could be a single or multidimensional array of column => value pairs
	 *
	 * @param array $data
	 * @return static|false
	 */
	public function create(array $data): static|false
	{
		$castedData = $this->castData($data);
		$created = $this->attachCreatedAt($castedData)->query->insert($castedData)->execute();

		if ($created) {
			return $this->find($this->connection->lastInsertId());
		}

		return false;
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
		$castedData = $this->castData($data);

		$query = $this->attachUpdatedAt($castedData)->query->update($this->table)->set($castedData);
		$query = $this->hasId() && $this->isQueryNotModified() ? $query->where($this->pk, $this->getId()) : $query;
		$this->attachQueries($query);

		$updated = $query->execute();

		if ($updated) {
			$this->oldAttributes = $oldAttributes;
			$this->attributes = [...$this->oldAttributes, ...$castedData];
			$this->removeHiddenAttributes();
			$this->removeHiddenOldAttributes();
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

		$this->attachQueries($query);

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

		if (empty($attributes) || empty($id = $this->getId())) {
			return $this;
		}

		return $this->find($id);
	}

	public function old(?string $attribute = null)
	{
		return empty($attribute) ? $this->oldAttributes : $this->oldAttributes[$attribute] ?? null;
	}

	public function toSql(): string
	{
		$query = $this->query->select('*');
		$this->attachQueries($query);

		return $query->toSql();
	}

	public function toRawSql(): string
	{
		$query = $this->query->select('*');
		$this->attachQueries($query);

		return $query->toRawSql();
	}

	public function toArray(): array
	{
		return array_map(function($attribute) {
			return match (true) {
				$attribute instanceof Model => iterator_to_array($attribute),
				$attribute instanceof Arrayable => array_values($attribute->toArray()),
				is_iterable($attribute) => array_values(iterator_to_array($attribute)),
				default => $attribute
			};
		}, iterator_to_array($this));
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
		$countClauses = count($this->queries);
		// If the model is soft deletable and the queries count is 1
		// It means there are no chained queries upon calling this method
		$softDeletableNoAddedQuery = $this->isSoftDeletable() && $countClauses === 1;

		// If the model is non-soft deletable and queries count is 0
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

	protected function addQuery(string $method, array $arguments): void
	{
		$this->queries[] = compact('method', 'arguments');
	}

	/**
	 * Attach the queries to the query object
	 *
	 * @param InsertQuery|SelectQuery|UpdateQuery|Delete $query
	 * @return void
	 */
	private function attachQueries(InsertQuery|SelectQuery|UpdateQuery|Delete $query): void
	{
		foreach ($this->queries as $clause) {
			$method = $clause['method'];
			$arguments = $clause['arguments'];

			$query->$method(...$arguments);
		}
	}

	private function attachCreatedAt(array &$data): static
	{
		return $this->handleTimestamp(static::CREATED_AT, $data);
	}

	private function attachUpdatedAt(array &$data): static
	{
		return $this->handleTimestamp(static::UPDATED_AT, $data);
	}

	private function handleTimestamp(string|false|null $timestampKey, array &$data): static
	{
		if (empty($timestampKey)) {
			return $this;
		}

		$provided = isset($data[$timestampKey]);
		$date = match (true) {
			$provided && $data[$timestampKey] === $this->old($timestampKey) => date(static::DATE_TIME_FORMAT),
			$provided && is_string($data[$timestampKey]) => date_create($data[$timestampKey]),
			$provided => $data[$timestampKey],
			default => date(static::DATE_TIME_FORMAT)
		};

		if ($date === false) {
			throw new InvalidArgumentException(sprintf("Invalid DateTime value provided for `%s` field", $timestampKey));
		}

		$data[$timestampKey] = $date instanceof DateTime
			? $date->format(static::DATE_TIME_FORMAT)
			: $date;

		$this->{$timestampKey} = $data[$timestampKey];

		return $this;
	}

	private function isSoftDeletable(): bool
	{
		return method_exists($this, 'softDelete') && defined('static::DELETED_AT');
	}

	private function setSofDelete()
	{
		if ($this->isSoftDeletable()) {
			$this->addQuery('whereNull', [static::DELETED_AT]);
		}
	}
}
