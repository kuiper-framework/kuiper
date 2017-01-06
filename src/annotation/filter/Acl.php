<?php
namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;
use kuiper\web\security\PermissionCheckerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Acl extends AbstractFilter
{
    /**
     * @Default
     * @Required
     * @var array<string>|string
     */
    public $resources;
    
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\Acl($container->get(PermissionCheckerInterface::class), $this->resources);
    }
}
