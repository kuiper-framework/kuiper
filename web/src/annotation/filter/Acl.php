<?php

namespace kuiper\web\annotation\filter;

use kuiper\web\middlewares;
use kuiper\web\security\PermissionCheckerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Acl extends AbstractFilter
{
    /**
     * @Default
     * @Required
     *
     * @var array<string>|string
     */
    public $resources;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\Acl($container->get(PermissionCheckerInterface::class), $this->resources);
    }
}
