<?php

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\exception\ReflectionException;

interface ReflectionNamespaceInterface
{
    public const NAMESPACE_SEPARATOR = '\\';

    /**
     * Gets the namespace name.
     */
    public function getNamespace(): string;

    /**
     * Gets all classes defined in the namespace.
     *
     * @return string[]
     *
     * @throws ReflectionException if file syntax error
     */
    public function getClasses(): array;
}
