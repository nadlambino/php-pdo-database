<?php

declare(strict_types=1);

namespace Inspira\Database;

use Closure;
use Inspira\Container\Container;
use Inspira\Database\Drivers\DriverInterface;
use Inspira\Database\Drivers\MySqlDriver;
use Inspira\Database\Drivers\PgSqlDriver;
use Inspira\Database\Drivers\SqliteDriver;
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
	public function __construct(protected Container $container, protected array $config)
	{
		$this->container->bind('mysql', MySqlDriver::class);
		$this->container->bind('pgsql', PgSqlDriver::class);
		$this->container->bind('sqlite', SqliteDriver::class);
	}

	public function create(): PDO
	{
		$driver = $this->container->getConcreteBinding($this->config['driver']);

		if (empty($driver)) {
			throw new RuntimeException("Connection driver for `$driver` is not found.");
		}

		$timezone = $this->config['timezone'] ?? '+00:00';
		$this->config['timezone'] = strtolower($timezone) === 'utc' ? '+00:00' : $timezone;

		$connection = match (true) {
			is_string($driver) => new $driver($this->config),
			$driver instanceof Closure => $this->container->resolve($driver),
			default => $driver
		};

		if (!($connection instanceof DriverInterface)) {
			throw new RuntimeException("Connection driver for `$driver` must be an instance of " . DriverInterface::class);
		}

		$pdo = $connection->connect();

		$this->container->singleton(PDO::class, fn(): PDO => $pdo);

		return $pdo;
	}
}
