<?php

declare(strict_types=1);

namespace Inspira\Database;

use Closure;
use Exception;
use Inspira\Container\Container;
use Inspira\Database\QueryBuilder\Query;
use Inspira\Database\Drivers\DriverInterface;
use Inspira\Database\Drivers\MySqlDriver;
use Inspira\Database\Drivers\PgSqlDriver;
use Inspira\Database\Drivers\Sqlite;
use PDO;
use RuntimeException;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Class ConnectionPool
 *
 * The ConnectionPool class is responsible for managing PDO connections to different database drivers.
 *
 * @package Inspira\Database
 */
class ConnectionPool
{
	/**
	 * An array to store and retrieve PDO instances based on connection names.
	 *
	 * @var array<string, PDO> $pool
	 */
	private array $pool = [];

	/**
	 * @param Container $container A dependency injection container for managing class dependencies.
	 * @param array $config An array of database configurations.
	 */
	public function __construct(protected Container $container, protected array $config)
	{
	}

	/**
	 * Check if a connection with the specified name exists in the pool.
	 *
	 * @param string $name The name of the database connection.
	 * @return bool True if the connection exists, false otherwise.
	 */
	public function has(string $name): bool
	{
		return isset($this->pool[$name]);
	}

	/**
	 * Get the PDO instance for the specified connection name.
	 *
	 * @param string $name The name of the database connection.
	 * @return PDO|null The PDO instance if found, null otherwise.
	 */
	public function get(string $name): ?PDO
	{
		if ($this->has($name)) {
			return $this->pool[$name];
		}

		$connectionName = $this->config[$name] ?? null;

		if (empty($connectionName)) {
			throw new RuntimeException("Connection `$name` is not defined.");
		}

		if (!isset($this->config['connections'])) {
			throw new RuntimeException("Connections are not defined in the configuration.");
		}

		$configuration = $this->config['connections'][$connectionName];

		if (!isset($configuration)) {
			throw new RuntimeException("Connection configuration for `$connectionName` is not defined.");
		}

		$factory = new ConnectionFactory($this->container, $configuration);
		$this->pool[$name] = $factory->create();

		return $this->pool[$name];
	}

	/**
	 * Get all connections stored in the pool.
	 *
	 * @return array An array of connection names and corresponding PDO instances.
	 */
	public function all(): array
	{
		return $this->pool;
	}
}
