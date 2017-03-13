<?php

namespace kuiper\di;

use Psr\Container\ContainerInterface as PsrContainer;

interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @param PsrContainer $container
     */
    public function setContainer(PsrContainer $container);
}
