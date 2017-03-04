<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class LoginOnly extends AbstractFilter
{
    /**
     * @var int
     */
    public $priority = 101;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return $container->get(middlewares\LoginOnly::class);
    }
}
