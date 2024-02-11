<?php

declare(strict_types=1);

namespace Inspira\Database;

use Closure;
use Inspira\Container\Container;
use Inspira\Database\Drivers\DriverInterface;
use Inspira\Database\Drivers\MySqlDriver;
use Inspira\Database\Drivers\PgSqlDriver;
use Inspira\Database\Drivers\Sqlite;
use PDO;
use RuntimeException;

/**
 * Class ConnectionFactory
 *
 * The ConnectionFactory class is responsible for creating a PDO connection.
 * It is also responsible for binding the supported connectors into the container and bind a singleton instance of the created PDO instance.
 */
class ConnectionFactory
{
	public function __construct(protected Container $container, protected array $config, protected string $name = 'default')
	{
		$this->container->bind('mysql', MySqlDriver::class);
		$this->container->bind('pgsql', PgSqlDriver::class);
		$this->container->bind('sqlite', Sqlite::class);
	}

	public function create(): PDO
	{
		$connectionName = $this->config[$this->name] ?? null;

		if (empty($connectionName)) {
			throw new RuntimeException("Unknown connection name `$this->name`.");
		}

		if (!isset($this->config['connections'])) {
			throw new RuntimeException("Connections configurations are not defined in the configuration array.");
		}

		$configuration = $this->config['connections'][$connectionName];

		if (!isset($configuration)) {
			throw new RuntimeException("Unknown connection configuration `$connectionName`.");
		}

		$driver = $this->container->getConcreteBinding($driver = $configuration['driver']);

		if (empty($driver)) {
			throw new RuntimeException("Connector class for `$driver` driver is not found.");
		}

		$timezone = $this->config['timezone'] ?? '+00:00';
		$configuration['timezone'] = strtolower($timezone) === 'utc' ? '+00:00' : $timezone;

		$connection = match (true) {
			is_string($driver) => new $driver($configuration),
			$driver instanceof Closure => $this->container->resolve($driver),
			default => $driver
		};

		if (!($connection instanceof DriverInterface)) {
			throw new RuntimeException("Connector class for `$driver` driver must be an instance of " . DriverInterface::class);
		}

		$pdo = $connection->connect();

		$this->container->singleton(PDO::class, fn(): PDO => $pdo);

		return $pdo;
	}
}
