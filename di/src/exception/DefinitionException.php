<?php

namespace kuiper\di\exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exception for the Container.
 */
class DefinitionException extends \RuntimeException implements ContainerExceptionInterface
{
}
