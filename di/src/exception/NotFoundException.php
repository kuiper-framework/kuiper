<?php

namespace kuiper\di\exception;

use Interop\Container\Exception\NotFoundException as BaseNotFoundException;
use RuntimeException;

/**
 * Exception thrown when a class or a value is not found in the container.
 */
class NotFoundException extends RuntimeException implements BaseNotFoundException
{
}
