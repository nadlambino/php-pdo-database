<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

use InvalidArgumentException;
use PDO;

class SqliteDriver extends Driver
{
	protected string $driver = 'sqlite';

	protected ?string $databaseUrl;

	private string $database;

	public function __construct(protected array $configs)
	{
		parent::__construct($this->configs);
		$this->databaseUrl = $this->configs['database_url'] ?? null;
		$this->database = $this->configs['database'] ?? '';
		$this->setAttributes();
	}

	public function connect(): PDO
	{
		$pdo = new PDO($this->getDsn(), options: $this->attributes);
		$this->executeCommands($pdo);

		return $pdo;
	}

	public function getDsn(): string
	{
		if (isset($this->databaseUrl) && !empty($this->databaseUrl)) {
			return $this->databaseUrl;
		}

		$this->validateDatabasePath();

		return "$this->driver:$this->database";
	}

	private function validateDatabasePath(): void
	{
		$path = realpath($this->database);

		if (!$path || !file_exists($path)) {
			throw new InvalidArgumentException("SQLite database file `$this->database` does not exist");
		}
	}
}
