<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

use Inspira\Collection\Collection;
use PDO;

class PgSqlDriver extends Driver
{
	use WithCredentials;

	protected string $driver = 'pgsql';

	protected ?string $databaseUrl;

	private string $host;

	private int $port;

	private string $database;

	public function __construct(protected array $configs)
	{
		parent::__construct($this->configs);
		$this->databaseUrl = $this->configs['database_url'] ?? null;
		$this->host = $this->configs['host'] ?? 'localhost';
		$this->port = (int)$this->configs['port'] ?? 5432;
		$this->database = $this->configs['database'] ?? '';
		$this->setCredentials();
	}

	public function connect(): PDO
	{
		[$username, $password] = $this->credentials;

		$pdo = new PDO($this->getDsn(), $username, $password, $this->attributes);
		$this->setTimezone($pdo);
		$this->executeCommands($pdo);

		return $pdo;
	}

	public function getDsn(): string
	{
		if (isset($this->databaseUrl) && !empty($this->databaseUrl)) {
			return $this->databaseUrl;
		}

		return "$this->driver:host=$this->host;dbname=$this->database;port=$this->port";
	}

	protected function setTimezone(PDO $pdo)
	{
		$commands = new Collection($this->commands);
		if (!is_null($commands->whereLike(null, 'TIME ZONE'))) {
			return;
		}

		$pdo->exec("SET TIME ZONE '$this->timezone'");
	}
}
