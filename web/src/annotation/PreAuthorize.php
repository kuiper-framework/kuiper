<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

use kuiper\web\middleware\AbstractMiddlewareFactory;
use kuiper\web\middleware\PreAuthorize as PreAuthorizeMiddleware;
use kuiper\web\security\AclInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class PreAuthorize extends AbstractMiddlewareFactory
{
    /**
     * @var string[]
     */
    public $value;

    /**
     * @var string[]
     */
    public $any;

    /**
     * {@inheritdoc}
     */
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        /** @phpstan-ignore-next-line */
        return new PreAuthorizeMiddleware($container->get(AclInterface::class), (array) $this->value, (array) $this->any);
    }
}
