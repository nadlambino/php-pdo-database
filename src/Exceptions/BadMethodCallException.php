<?php

declare(strict_types=1);

namespace Inspira\Database\Exceptions;

use BadMethodCallException as BaseBadMethodCallException;
use Throwable;

class BadMethodCallException extends BaseBadMethodCallException
{
	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, string $method = null)
	{
		$message = empty($message) ? "Call to undefined method `$method`." : $message;
		$class = $this->getCallingClass();

		if (isset($class, $method)) {
			$methods = get_class_methods($class);
			$closest = closest_match($method, $methods);
			$message .= $closest ? " Did you mean `$closest`?" : '';
		}

		parent::__construct($message, $code, $previous);
	}

	protected function getCallingClass(): ?string
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);

		if (isset($trace[2]['object']) && is_object($object = $trace[2]['object']) && !($object instanceof $this)) {
			return get_class($object);
		}

		return null;
	}
}
