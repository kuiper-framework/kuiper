<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

use kuiper\web\middleware\AbstractMiddlewareFactory;
use kuiper\web\middleware\CsrfToken as CsrfTokenMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class CsrfToken extends AbstractMiddlewareFactory
{
    /**
     * @var bool
     */
    public $repeatOk = true;

    /**
     * {@inheritdoc}
     */
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return new CsrfTokenMiddleware($this->repeatOk);
    }
}
