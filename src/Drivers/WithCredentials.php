<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

trait WithCredentials
{
	protected array $credentials;

	protected function setCredentials(string $defaultUsername = '', string $defaultPassword = ''): void
	{
		if (empty($credentials = $this->configs['credentials'])) {
			$this->credentials = [$defaultUsername, $defaultPassword];
			return;
		}

		$this->credentials = [$credentials['username'] ?? $defaultUsername, $credentials['password'] ?? $defaultPassword];
	}
}
