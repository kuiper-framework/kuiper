<?php

namespace kuiper\reflection;

interface ReflectionFileFactoryInterface
{
    /**
     * Creates ReflectionFileInterface instance.
     *
     * @param string $file
     *
     * @return ReflectionFileInterface
     */
    public function create($file): ReflectionFileInterface;
}
