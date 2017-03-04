<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;

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
