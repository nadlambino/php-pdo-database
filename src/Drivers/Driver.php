<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

use InvalidArgumentException;
use PDO;

abstract class Driver implements DriverInterface
{
	protected array $attributes = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES => true,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_AUTOCOMMIT => true
	];

	protected array $commands;

	protected string $timezone;

	public function __construct(protected array $configs)
	{
		$this->commands = $this->configs['commands'] ?? [];
		$this->timezone = $this->configs['timezone'] ?: 'UTC';
		$this->setAttributes();
	}

	abstract public function connect(): PDO;

	protected function setAttributes()
	{
		$attributes = $this->configs['attributes'] ?? [];

		if (empty($attributes)) {
			return;
		}

		if (!is_array($attributes)) {
			throw new InvalidArgumentException("PDO attributes must be an array.");
		}

		// Merging arrays that overrides the value of right array
		$this->attributes = $attributes + $this->attributes;
	}

	protected function executeCommands(PDO $pdo)
	{
		if (!empty($this->commands)) {
			$commands = array_map(fn($value) => rtrim($value, ';'), $this->commands);
			$pdo->exec(implode(';', $commands));
		}
	}
}
