<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Json extends AbstractFilter
{
    /**
     * @var int
     */
    public $priority = 100;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\Json();
    }
}
