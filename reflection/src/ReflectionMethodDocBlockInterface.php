<?php

declare(strict_types=1);

namespace kuiper\reflection;

interface ReflectionMethodDocBlockInterface extends ReflectionDocBlockInterface
{
    /**
     * Parses the docblock of the method to get all parameters type.
     *
     * @return ReflectionTypeInterface[] the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterTypes(): array;

    /**
     * Parses the docblock of the method to get return type.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnType(): ReflectionTypeInterface;
}
