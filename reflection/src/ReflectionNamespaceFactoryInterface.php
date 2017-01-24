<?php

namespace kuiper\reflection;

interface ReflectionNamespaceFactoryInterface
{
    /**
     * Creates instance.
     *
     * @return self
     */
    public static function createInstance();

    /**
     * Creates ReflectionNamespaceInterface instance.
     *
     * @param string $namespace
     *
     * @return ReflectionNamespaceInterface
     */
    public function create($namespace);

    /**
     * Clears cached instance.
     */
    public function clearCache($namespace = null);
}
