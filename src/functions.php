<?php

use Inspira\Container\Container;
use Inspira\Database\Builder\Raw;

if (!function_exists('get_short_class_name')) {
	function get_short_class_name(string $class): string
	{
		return strtolower(basename(str_replace('\\', '/', $class)));
	}
}

if (!function_exists('pdo_quote')) {
	function pdo_quote(Raw|string|null $string, ?PDO $connection = null): string
	{
		/** @var PDO $connection */
		$connection ??= Container::getInstance()->make(PDO::class);
		$driver = $connection?->getAttribute(PDO::ATTR_DRIVER_NAME);

		return match (true) {
			is_null($string) => '',
			$string instanceof Raw => (string)$string,
			in_array($driver, ['mysql', 'sqlite']) => "`$string`",
			default => '"' . $string . '"'
		};
	}
}

if (!function_exists('pdo_type')) {
	function pdo_type(mixed $value): int
	{
		$type = gettype($value);

		return match (true) {
			$type === 'NULL' => PDO::PARAM_NULL,
			$type === 'boolean' => PDO::PARAM_BOOL,
			$type === 'integer' => PDO::PARAM_INT,
			$type === 'resource' => PDO::PARAM_LOB,
			default => PDO::PARAM_STR,
		};
	}
}
