<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface as BaseContainer;
use kuiper\di\exception\DependencyException;
use kuiper\di\exception\NotFoundException;

interface ContainerInterface extends BaseContainer
{
    /**
     * Resolves an entry by its name. If given a class name, it will return a new instance of that class.
     *
     * @param string $name       entry name or a class name
     * @param array  $parameters Optional parameters to use to build the entry. Use this to force specific
     *                           parameters to specific values. Parameters not defined in this array will
     *                           be automatically resolved.
     *
     * @throws \InvalidArgumentException the name parameter must be of type string
     * @throws DependencyException       error while resolving the entry
     * @throws NotFoundException         no entry or class found for the given name
     *
     * @return mixed
     */
    public function make($name, $parameters = []);

    /**
     * @param string $name
     * @param mixed  $definition
     *
     * @return self
     */
    public function set($name, $definition);

    /**
     * Events hook for request start.
     */
    public function startRequest();

    /**
     * Events hoot for request end.
     */
    public function endRequest();
}
