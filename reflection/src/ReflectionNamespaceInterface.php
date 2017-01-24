<?php

namespace kuiper\reflection;

interface ReflectionNamespaceInterface
{
    /**
     * Gets the namespace name.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Gets all classes defined in the namespace.
     *
     * @return string[]
     */
    public function getClasses();
}
