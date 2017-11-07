<?php

namespace kuiper\annotations;

use kuiper\reflection\ReflectionTypeInterface;
use ReflectionMethod;
use ReflectionProperty;

interface DocReaderInterface
{
    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @return ReflectionTypeInterface
     *
     * @throws exception\ClassNotFoundException
     */
    public function getPropertyType(ReflectionProperty $property);

    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @return string|null
     *
     * @throws exception\ClassNotFoundException
     */
    public function getPropertyClass(ReflectionProperty $property);

    /**
     * Parses the docblock of the method to get all parameters type.
     *
     * @param ReflectionMethod $method
     *
     * @return ReflectionTypeInterface[] the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterTypes(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get all class type of parameters.
     *
     * @param ReflectionMethod $method
     *
     * @return string[] the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterClasses(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get return type.
     *
     * @param ReflectionMethod $method
     *
     * @return ReflectionTypeInterface
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnType(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get return class type.
     *
     * @param ReflectionMethod $method
     *
     * @return string|null
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnClass(ReflectionMethod $method);
}
