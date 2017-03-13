<?php

namespace kuiper\web\annotation\filter;

use kuiper\web\middlewares;
use Psr\Container\ContainerInterface;

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
