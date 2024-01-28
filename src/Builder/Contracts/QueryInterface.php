<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Contracts;

interface QueryInterface
{
	public function toSql(): string;

	public function execute(): mixed;
}
