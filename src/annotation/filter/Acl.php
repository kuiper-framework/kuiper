<?php
namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\security\PermissionCheckerInterface;
use kuiper\web\middlewares;

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
