<?php

declare(strict_types=1);

namespace Inspira\Database\Drivers;

use PDO;

interface DriverInterface
{
	public function connect(): PDO;
}
