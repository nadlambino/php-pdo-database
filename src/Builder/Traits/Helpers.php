<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Traits;

use Inspira\Database\Builder\Enums\Reserved;
use Inspira\Database\Builder\Raw;
use Inspira\Database\Builder\Select;
use PDO;

trait Helpers
{
	/**
	 * This holds the bound parameters to the query where key is the placeholder
	 * We can't put it on the Base class because this is needed by sub-clause classes
	 * And this trait is being injected to those sub-clause classes
	 *
	 * @var array $parameters
	 */
	protected array $parameters = [];

	public function getParameters(): array
	{
		return $this->parameters;
	}

	protected function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	protected function addParameter(string $placeholder, mixed $value): void
	{
		$this->parameters[$placeholder] = $value;
	}

	protected function mergeParameters(array $parameters): void
	{
		$this->parameters = [...$this->getParameters(), ...$parameters];
	}

	protected function generatePlaceholder(string $column, ?string $prefix = null, ?string $suffix = null): string
	{
		$prefix ??= $this->table;
		$suffix ??= count($this->getParameters());
		$placeholder = implode('_', [':' . $prefix, $column, $suffix]);

		return str_replace(['.', ' '], '_', $placeholder);
	}

	protected function addConditions(Reserved $clause, ?Reserved $operator, bool $grouped, array $parameters): static
	{
		$clause = strtolower($clause->value . 's');
		$operator = empty($this->$clause) ? null : $operator->value;
		$this->$clause[] = compact('operator', 'grouped', 'parameters');

		return $this;
	}

	/**
	 * @param array $conditions
	 * @param string $for (WHERE or HAVING)
	 * @return string
	 */
	protected function getConditions(array $conditions, string $for): string
	{
		$clause = '';

		foreach ($conditions as $condition) {
			$operator = $condition['operator'];

			// If it's a nested group, recursively generate the nested WHERE clause
			if (isset($condition['grouped']) && $condition['grouped'] === true) {
				$groupedWhere = $this->getConditions($condition['parameters'], $for);
				$clause .= $this->concat(' ', $operator, '(', $groupedWhere, ')');
				continue;
			}

			// If it's a raw query, accept it and continue to the next condition
			if (isset($condition['parameters']['raw'])) {
				$clause .= $this->concat(' ', $operator, ' ', $condition['parameters']['raw']);
				continue;
			}

			// Regular condition with named placeholder
			$rawColumn = $condition['parameters']['column'];
			$column = $for === Reserved::WHERE->value ? $this->getFormattedColumn($rawColumn) : $this->quote($rawColumn);
			$value = $condition['parameters']['value'];
			$comparison = $condition['parameters']['comparison'];
			$table = $condition['parameters']['table'] ?? null;

			// Handles WHERE EXISTS query
			if (isset($table)) {
				$quotedTable = $this->quote($table);
				$quotedParentTable = $this->quote($this->table);
				$quotedTableColumn = $this->quote($rawColumn);
				$quotedParentTableColumn = $this->quote($value);
				$exists = Reserved::EXISTS->value;

				$sql = (new Select([$rawColumn], $this->connection, $this->inflector))
					->from($table)
					->whereRaw("$quotedTable.$quotedTableColumn $comparison $quotedParentTable.$quotedParentTableColumn")
					->toSql();

				$clause .= " $operator $exists ($sql) ";
			}

			// Handles WHERE BETWEEN query
			else if (str_contains($comparison, Reserved::BETWEEN->value) && is_array($value)) {
				$count = count($this->getParameters());
				[$lowerBound, $upperBound] = $value;
				$lowerBoundPlaceholder = $this->generatePlaceholder($rawColumn, suffix: (string)$count);
				$upperBoundPlaceholder = $this->generatePlaceholder($rawColumn, suffix: (string)($count + 1));
				$clause .= $this->concat(' ', $operator, $column, $comparison, $lowerBoundPlaceholder, Reserved::AND->value, $upperBoundPlaceholder);
				$this->addParameter($lowerBoundPlaceholder, $lowerBound);
				$this->addParameter($upperBoundPlaceholder, $upperBound);
			}

			// Handles WHERE IN query
			else if (str_contains($comparison, Reserved::IN->value) && is_array($value)) {
				$placeholders = '';
				foreach ($value as $v) {
					$placeholder = $this->generatePlaceholder($rawColumn);
					$placeholders .= ', ' . $placeholder;
					$this->addParameter($placeholder, $v);
				}
				$placeholders = trim($placeholders, ', ');
				$clause .= $this->concat(' ', $operator, $column, $comparison, "($placeholders)");
			}

			// Handles WHERE query
			else {
				$placeholder = $this->generatePlaceholder($rawColumn);
				$clause .= $this->concat(' ', $operator, $column, $comparison, $placeholder);
				$this->addParameter($placeholder, $value);
			}
		}

		return $clause;
	}

	protected function getConditionalParams(string $column, mixed $comparison = null, mixed $value = null, int $type = PDO::PARAM_STR): array
	{
		$comparison = $comparison instanceof Reserved ? $comparison->value : $comparison;
		$newValue = $value ?? $comparison;
		if ($type !== PDO::PARAM_NULL) {
			$comparison = is_null($value) ? '=' : $comparison;
			$value = $newValue;
		}

		return compact('column', 'comparison', 'value');
	}

	protected function quote(string|Raw|null $string): string
	{
		$driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);

		return match (true) {
			is_null($string) => '',
			$string instanceof Raw => (string)$string,
			in_array($driver, ['mysql', 'sqlite']) => "`$string`",
			default => '"' . $string . '"'
		};
	}

	protected function concat(...$strings): string
	{
		return implode(' ', $strings);
	}

	protected function type(mixed $value): int
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

	protected function getFormattedColumn(string $column): string
	{
		$exploded = explode('.', $column);
		$table = $this->quote($exploded[0]);
		$column = $exploded[1] ?? $exploded[0];
		$column = $column === Reserved::ALL->value ? $column : $this->quote($column);

		return isset($exploded[1]) ? "$table.$column" : $column;
	}

	protected function trimWhiteSpace(string $string): string
	{
		return preg_replace('/\s+/', ' ', trim($string));
	}
}
