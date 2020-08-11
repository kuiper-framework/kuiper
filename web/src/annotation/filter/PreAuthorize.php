<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\middleware\PreAuthorize as PreAuthorizeMiddleware;
use kuiper\web\security\AclInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class PreAuthorize extends AbstractFilter
{
    /**
     * @var string[]
     */
    public $value;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container): ?MiddlewareInterface
    {
        return new PreAuthorizeMiddleware($container->get(AclInterface::class), $this->value);
    }
}
