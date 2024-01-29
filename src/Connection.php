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
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

class Connection
{
	/**
	 * @var array<string, PDO> $pool
	 */
	private static array $pool = [];

	private string $name = 'default';

	public function __construct(protected Container $container, protected Config $config)
	{
		$this->registerDatabaseServices();
	}

	protected function registerDatabaseServices(): void
	{
		$this->container->singleton(PDO::class, fn() => $this->create());
		$this->container->singleton(InflectorInterface::class, EnglishInflector::class);
		$this->container->bind(Query::class);

		// PDO Connectors
		$this->container->bind('mysql', MySql::class);
		$this->container->bind('pgsql', PgSql::class);
		$this->container->bind('sqlite', Sqlite::class);
	}

	public function name(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function create(): PDO
	{
		if (Connection::has($this->name)) {
			return Connection::get($this->name);
		}

		$name = $this->config->get("database.$this->name");

		if (empty($name)) {
			throw new Exception("Unknown connection name `$this->name`.");
		}

		$configs = $this->config->get("database.connections.$name");

		if (empty($configs)) {
			throw new Exception("Unknown connection configuration `$name`.");
		}

		if (!is_array($configs)) {
			throw new Exception("Connection structure must be an array containing its configurations.");
		}

		$connector = $this->container->getConcreteBinding($driver = $configs['driver']);

		if (empty($connector)) {
			throw new Exception("Connector class for `$driver` driver is not found.");
		}

		$timezone = $this->config->get('database.timezone') ?? $this->config->get('app.timezone', '+00:00');
		$configs['timezone'] = strtolower($timezone) === 'utc' ? '+00:00' : $timezone;

		$connection = match (true) {
			is_string($connector) => new $connector($configs),
			$connector instanceof Closure => $this->container->resolve($connector),
			default => $connector
		};

		if (!($connection instanceof ConnectorInterface)) {
			throw new Exception("Connector class for `$driver` driver must be an instance of " . ConnectorInterface::class);
		}

		return static::$pool[$this->name] = $connection->connect();
	}

	public static function has(string $name): bool
	{
		return isset(self::$pool[$name]);
	}

	public static function get(string $name): ?PDO
	{
		return self::$pool[$name] ?? null;
	}

	public static function all(): array
	{
		return self::$pool;
	}
}
