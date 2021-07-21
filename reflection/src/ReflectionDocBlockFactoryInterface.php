<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionDocBlockFactoryInterface
{
    public function createPropertyDocBlock(\ReflectionProperty $property): ReflectionPropertyDocBlockInterface;

    public function createMethodDocBlock(\ReflectionMethod $method): ReflectionMethodDocBlockInterface;
}
