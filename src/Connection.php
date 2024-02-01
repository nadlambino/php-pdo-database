<?php

declare(strict_types=1);

namespace Inspira\Database;

use Closure;
use Exception;
use Inspira\Config\Config;
use Inspira\Container\Container;
use Inspira\Database\Builder\Query;
use Inspira\Database\Connectors\ConnectorInterface;
use Inspira\Database\Connectors\MySql;
use Inspira\Database\Connectors\PgSql;
use Inspira\Database\Connectors\Sqlite;
use PDO;
use RuntimeException;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Class Connection
 *
 * The Connection class is responsible for managing and creating PDO connections to different database drivers.
 * It supports MySQL, PostgreSQL, and SQLite database connections.
 *
 * @package Inspira\Database
 */
class Connection
{
	/**
	 * An array to store and retrieve PDO instances based on connection names.
	 *
	 * @var array<string, PDO> $pool
	 */
	private static array $pool = [];

	/**
	 * The name of the database connection. Defaults to 'default'.
	 */
	private string $name = 'default';

	/**
	 * @param Container $container  A dependency injection container for managing class dependencies.
	 * @param Config $config A config class instance.
	 */
	public function __construct(protected Container $container, protected Config $config)
	{
		// Registering singletons and bindings in the container.
		$this->container->singleton(PDO::class, fn() => $this->create());
		$this->container->singleton(InflectorInterface::class, EnglishInflector::class);
		$this->container->bind(Query::class);

		// PDO Connectors
		$this->container->bind('mysql', MySql::class);
		$this->container->bind('pgsql', PgSql::class);
		$this->container->bind('sqlite', Sqlite::class);
	}

	/**
	 * Set the name of the database connection.
	 *
	 * @param string $name The name of the database connection.
	 * @return $this
	 */
	public function name(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Create a new PDO connection based on the configured database connection.
	 *
	 * @return PDO The PDO instance representing the database connection.
	 * @throws Exception If there are issues creating the PDO connection.
	 */
	public function create(): PDO
	{
		// If the connection already exists in the pool, return it.
		if (Connection::has($this->name)) {
			return Connection::get($this->name);
		}

		// Get the connection name from the configuration.
		$name = $this->config->get("database.$this->name");

		// Throw an exception if the connection name is not found in the configuration.
		if (empty($name)) {
			throw new RuntimeException("Unknown connection name `$this->name`.");
		}

		// Get the connection configurations from the configuration file.
		$configs = $this->config->get("database.connections.$name");

		// Throw an exception if the connection configurations are not found.
		if (empty($configs)) {
			throw new RuntimeException("Unknown connection configuration `$name`.");
		}

		if (!is_array($configs)) {
			throw new RuntimeException("Connection structure must be an array containing its configurations.");
		}

		$connector = $this->container->getConcreteBinding($driver = $configs['driver']);

		if (empty($connector)) {
			throw new RuntimeException("Connector class for `$driver` driver is not found.");
		}

		$timezone = $this->config->get('database.timezone') ?? $this->config->get('app.timezone', '+00:00');
		$configs['timezone'] = strtolower($timezone) === 'utc' ? '+00:00' : $timezone;

		$connection = match (true) {
			is_string($connector) => new $connector($configs),
			$connector instanceof Closure => $this->container->resolve($connector),
			default => $connector
		};

		if (!($connection instanceof ConnectorInterface)) {
			throw new RuntimeException("Connector class for `$driver` driver must be an instance of " . ConnectorInterface::class);
		}

		return static::$pool[$this->name] = $connection->connect();
	}

	/**
	 * Check if a connection with the specified name exists in the pool.
	 *
	 * @param string $name The name of the database connection.
	 * @return bool True if the connection exists, false otherwise.
	 */
	public static function has(string $name): bool
	{
		return isset(self::$pool[$name]);
	}

	/**
	 * Get the PDO instance for the specified connection name.
	 *
	 * @param string $name The name of the database connection.
	 * @return PDO|null The PDO instance if found, null otherwise.
	 */
	public static function get(string $name): ?PDO
	{
		return self::$pool[$name] ?? null;
	}

	/**
	 * Get all connections stored in the pool.
	 *
	 * @return array An array of connection names and corresponding PDO instances.
	 */
	public static function all(): array
	{
		return self::$pool;
	}
}
