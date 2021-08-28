<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionPropertyDocBlockInterface extends ReflectionDocBlockInterface
{
    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getType(): ReflectionTypeInterface;
}
