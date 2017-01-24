<?php

namespace kuiper\di\exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException;

/**
 * Exception for the Container.
 */
class DependencyException extends RuntimeException implements ContainerException
{
}
