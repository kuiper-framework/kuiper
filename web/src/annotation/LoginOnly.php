<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

use kuiper\web\middleware\AbstractMiddlewareFactory;
use kuiper\web\middleware\LoginOnly as LoginOnlyMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class LoginOnly extends AbstractMiddlewareFactory
{
    /**
     * @var int
     */
    public $priority = 101;

    /**
     * {@inheritdoc}
     */
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return new LoginOnlyMiddleware();
    }
}
