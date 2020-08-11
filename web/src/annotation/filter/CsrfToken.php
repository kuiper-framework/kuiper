<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\middleware\CsrfToken as CsrfTokenMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class CsrfToken extends AbstractFilter
{
    /**
     * @var bool
     */
    public $repeatOk = true;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container): ?MiddlewareInterface
    {
        return new CsrfTokenMiddleware($this->repeatOk);
    }
}
