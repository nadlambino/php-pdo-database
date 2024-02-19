<?php

use Inspira\Container\Container;
use Inspira\Database\QueryBuilder\RawQuery;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

if (!function_exists('pdo_quote')) {
	function pdo_quote(RawQuery|string|null $string, ?PDO $connection = null): string
	{
		/** @var PDO $connection */
		$connection ??= Container::getInstance()->make(PDO::class);
		$driver = $connection?->getAttribute(PDO::ATTR_DRIVER_NAME);

		return match (true) {
			is_null($string) => '',
			$string instanceof RawQuery => $string->toRawSql(),
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

if (!function_exists('singularize')) {
	function singularize(string $word, ?InflectorInterface $inflector = null): string
	{
		$inflector ??= new EnglishInflector();

		return $inflector->singularize($word)[0] ?? $word;
	}
}

if (!function_exists('pluralize')) {
	function pluralize(string $word, ?InflectorInterface $inflector = null): string
	{
		$inflector ??= new EnglishInflector();

		return $inflector->pluralize($word)[0] ?? $word;
	}
}
