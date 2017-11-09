<?php

namespace kuiper\di;

use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\EnvDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\NamedParameters;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\definition\StringDefinition;

if (!function_exists('\kuiper\di\get')) {
    /**
     * reference another definition.
     *
     * @param string $alias
     *
     * @return AliasDefinition
     */
    function get($alias)
    {
        return new AliasDefinition($alias);
    }

    /**
     * define entry using a factory function.
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return FactoryDefinition
     */
    function factory($callable, ...$args)
    {
        return new FactoryDefinition($callable, $args);
    }

    /**
     * define an object entry.
     *
     * @param string $class
     *
     * @return ObjectDefinition
     */
    function object($class = null)
    {
        return new ObjectDefinition($class);
    }

    /**
     * @param array $params
     *
     * @return NamedParameters
     */
    function params(array $params)
    {
        return new NamedParameters($params);
    }

    /**
     * define an environment entry.
     *
     * @param string $name    name of environment
     * @param string $default
     *
     * @return EnvDefinition
     */
    function env($name, $default = null)
    {
        return new EnvDefinition($name, $default);
    }

    /**
     * define an string entry.
     *
     * @param string $expression
     *
     * @return StringDefinition
     */
    function string($expression)
    {
        return new StringDefinition($expression);
    }
}
