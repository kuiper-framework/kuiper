<?php

namespace kuiper\web\annotation\filter;

use kuiper\web\middlewares;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class PostOnly extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\PostOnly();
    }
}
