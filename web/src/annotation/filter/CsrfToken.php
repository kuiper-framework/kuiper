<?php

namespace kuiper\web\annotation\filter;

use kuiper\web\middlewares;
use kuiper\web\security\CsrfTokenInterface;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class CsrfToken extends AbstractFilter
{
    /**
     * @var bool
     */
    public $repeatOk = false;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\CsrfToken($container->get(CsrfTokenInterface::class), $this->repeatOk);
    }
}
