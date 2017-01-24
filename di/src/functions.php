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
     * @return DefinitionInterface
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
     * @return DefinitionInterface
     */
    function factory($callable)
    {
        $args = func_get_args();
        array_shift($args);

        return new FactoryDefinition($callable, $args);
    }

    /**
     * define an object entry.
     *
     * @param string $class
     *
     * @return DefineInterface
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
     * @return DefinitionInterface
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
     * @return DefinitionInterface
     */
    function string($expression)
    {
        return new StringDefinition($expression);
    }
}
