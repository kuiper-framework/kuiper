<?php

namespace kuiper\web\annotation\filter;

use Psr\Container\ContainerInterface;

interface FilterInterface
{
    /**
     * filter priority, the smaller one run first.
     *
     * @return int
     */
    public function getPriority();

    /**
     * @param ContainerInterface $container
     *
     * @return callable
     */
    public function createMiddleware(ContainerInterface $container);
}
