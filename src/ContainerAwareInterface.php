<?php
namespace kuiper\di;

use Interop\Container\ContainerInterface as InteropContainer;

interface ContainerAwareInterface
{
    /**
     * Sets the container
     *
     * @param $container
     *
     * @return void
     */
    public function setContainer(InteropContainer $container);
}
