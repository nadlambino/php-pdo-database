<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

interface QueryInterface
{
	public function toSql(): string;

	public function execute(): mixed;
}
