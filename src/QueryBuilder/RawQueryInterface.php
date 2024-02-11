<?php

declare(strict_types=1);

namespace Inspira\Database\QueryBuilder;

interface RawQueryInterface
{
	public function toRawSql(): string;
}
