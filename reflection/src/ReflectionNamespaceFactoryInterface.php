<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionNamespaceFactoryInterface
{
    /**
     * Creates ReflectionNamespaceInterface instance.
     *
     * @param string $namespace
     *
     * @return ReflectionNamespaceInterface
     */
    public function create($namespace): ReflectionNamespaceInterface;
}
