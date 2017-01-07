<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface as InteropContainer;

trait ContainerAwareTrait
{
    protected $container;

    /**
     * Sets the container.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(InteropContainer $container)
    {
        $this->container = $container;
    }
}
