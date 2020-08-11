<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\middleware\LoginOnly as LoginOnlyMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

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
    public function createMiddleware(ContainerInterface $container): ?MiddlewareInterface
    {
        return new LoginOnlyMiddleware();
    }
}
