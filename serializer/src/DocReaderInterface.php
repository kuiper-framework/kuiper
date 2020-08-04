<?php

declare(strict_types=1);

namespace kuiper\serializer;

use kuiper\reflection\ReflectionTypeInterface;
use ReflectionMethod;
use ReflectionProperty;

interface DocReaderInterface
{
    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getPropertyType(ReflectionProperty $property): ReflectionTypeInterface;

    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getPropertyClass(ReflectionProperty $property): ?string;

    /**
     * Parses the docblock of the method to get all parameters type.
     *
     * @return ReflectionTypeInterface[] the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterTypes(ReflectionMethod $method): array;

    /**
     * Parses the docblock of the method to get all class type of parameters.
     *
     * @return string[] the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterClasses(ReflectionMethod $method): array;

    /**
     * Parses the docblock of the method to get return type.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnType(ReflectionMethod $method): ReflectionTypeInterface;

    /**
     * Parses the docblock of the method to get return class type.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnClass(ReflectionMethod $method): ?string;
}
