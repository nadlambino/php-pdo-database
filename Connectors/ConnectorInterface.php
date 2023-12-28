<?php

declare(strict_types=1);

namespace Inspira\Database\Connectors;

use PDO;

interface ConnectorInterface
{
	public function connect(): PDO;
}
