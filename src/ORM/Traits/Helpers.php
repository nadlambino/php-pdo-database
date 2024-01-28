<?php

namespace Inspira\Database\ORM\Traits;

trait Helpers
{
	protected function getShortClassName(string $model): string
	{
		return strtolower(basename(str_replace('\\', '/', $model)));
	}
}
