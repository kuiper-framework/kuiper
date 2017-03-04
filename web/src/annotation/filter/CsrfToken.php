<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;
use kuiper\web\security\CsrfTokenInterface;

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
