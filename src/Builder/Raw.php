<?php

declare(strict_types=1);

namespace Inspira\Database\Builder;

class Raw
{
	private string $query;

	public function __construct(string $query = '')
	{
		$this->query = $query;
	}

	public function query(string $query): static
	{
		$this->query = $query;

		return $this;
	}

	public function __toString(): string
	{
		return $this->query;
	}
}
