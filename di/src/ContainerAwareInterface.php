<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface as InteropContainer;

interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @param InteropContainer $container
     */
    public function setContainer(InteropContainer $container);
}
