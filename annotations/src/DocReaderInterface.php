<?php

namespace kuiper\annotations;

use ReflectionMethod;
use ReflectionProperty;

interface DocReaderInterface
{
    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @throws exception\ClassNotFoundException
     *
     * @return \kuiper\reflection\ReflectionType Type of the property
     */
    public function getPropertyType(ReflectionProperty $property);

    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @throws exception\ClassNotFoundException
     *
     * @return string|null Type of the property (content of var annotation)
     */
    public function getPropertyClass(ReflectionProperty $property);

    /**
     * Parses the docblock of the method to get all parameters type.
     *
     * @param ReflectionMethod $method
     *
     * @return array key is parameter name, value is ReflectionType
     */
    public function getParameterTypes(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get all class type of parameters.
     *
     * @param ReflectionMethod $method
     *
     * @return array key is parameter name, value is the class name
     */
    public function getParameterClasses(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get return type.
     *
     * @param ReflectionMethod $method
     *
     * @return \kuiper\reflection\ReflectionType
     */
    public function getReturnType(ReflectionMethod $method);

    /**
     * Parses the docblock of the method to get return class type.
     *
     * @param ReflectionMethod $method
     *
     * @return string|null
     */
    public function getReturnClass(ReflectionMethod $method);
}
