<?php

declare(strict_types=1);

namespace Inspira\Database\ORM\Traits;

trait WithTimestamps
{
	protected const CREATED_AT = 'created_at';

	protected const UPDATED_AT = 'updated_at';

	protected function attachCreatedAt(array &$data): static
	{
		if (static::CREATED_AT) {
			$data[static::CREATED_AT] = date('Y-m-d H:i:s');
		}

		return $this;
	}

	protected function attachUpdatedAt(array &$data): static
	{
		if (static::UPDATED_AT) {
			$data[static::UPDATED_AT] = date('Y-m-d H:i:s');
		}

		return $this;
	}
}
