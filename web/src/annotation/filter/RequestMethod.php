<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class RequestMethod extends AbstractFilter
{
    /**
     * @Default
     *
     * @var array
     */
    public $methods;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new \kuiper\web\middlewares\RequestMethod($this->methods);
    }
}
