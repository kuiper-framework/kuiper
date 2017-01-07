<?php

namespace kuiper\di\exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException;

class AnnotationException extends RuntimeException implements ContainerException
{
}
