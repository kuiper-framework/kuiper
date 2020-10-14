<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionFileFactoryInterface
{
    /**
     * Creates ReflectionFileInterface instance.
     */
    public function create(string $file): ReflectionFileInterface;
}
