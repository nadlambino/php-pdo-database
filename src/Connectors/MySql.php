<?php

declare(strict_types=1);

namespace Inspira\Database\Connectors;

use PDO;

class MySql extends Connector
{
	use WithCredentials;

	protected string $driver = 'mysql';

	protected ?string $databaseUrl;

	protected string $host;

	protected int $port;

	protected string $database;

	public function __construct(protected array $configs)
	{
		parent::__construct($this->configs);
		$this->databaseUrl = $this->configs['database_url'] ?? null;
		$this->host = $this->configs['host'] ?? 'localhost';
		$this->port = (int)$this->configs['port'] ?? 3306;
		$this->database = $this->configs['database'] ?? '';
		$this->setCredentials('root');
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
		$pdo->exec("SET time_zone = '$this->timezone'");
	}
}
