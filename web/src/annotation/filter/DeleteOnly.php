<?php

namespace kuiper\web\annotation\filter;

use kuiper\web\middlewares;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class DeleteOnly extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\DeleteOnly();
    }
}
