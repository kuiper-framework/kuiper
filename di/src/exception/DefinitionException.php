<?php

namespace kuiper\di\exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException;

/**
 * Exception for the Container.
 */
class DefinitionException extends RuntimeException implements ContainerException
{
}
