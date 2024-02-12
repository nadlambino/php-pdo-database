<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

trait WithCredentials
{
	protected array $credentials;

	protected function setCredentials(string $defaultUsername = '', string $defaultPassword = ''): void
	{
		$username = $this->configs['username'] ?? $defaultUsername;
		$password = $this->configs['password'] ?? $defaultPassword;

		$this->credentials = [$username, $password];
	}
}
