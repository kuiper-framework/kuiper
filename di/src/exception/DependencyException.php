<?php

namespace kuiper\di\exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exception for the Container.
 */
class DependencyException extends \RuntimeException implements ContainerExceptionInterface
{
}
