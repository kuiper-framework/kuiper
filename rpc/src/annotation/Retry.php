<?php

declare(strict_types=1);

namespace kuiper\rpc\annotation;

use kuiper\rpc\AbstractMiddlewareFactory;
use kuiper\rpc\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Retry extends AbstractMiddlewareFactory
{
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return $container->get(\kuiper\rpc\client\middleware\Retry::class);
    }
}
