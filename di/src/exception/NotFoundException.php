<?php

namespace kuiper\di\exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a class or a value is not found in the container.
 */
class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
