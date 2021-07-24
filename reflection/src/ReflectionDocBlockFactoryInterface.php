<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionDocBlockFactoryInterface
{
    /**
     * @param \ReflectionProperty $property
     *
     * @return ReflectionPropertyDocBlockInterface
     */
    public function createPropertyDocBlock(\ReflectionProperty $property): ReflectionPropertyDocBlockInterface;

    /**
     * @param \ReflectionMethod $method
     *
     * @return ReflectionMethodDocBlockInterface
     */
    public function createMethodDocBlock(\ReflectionMethod $method): ReflectionMethodDocBlockInterface;
}
