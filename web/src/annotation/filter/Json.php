<?php
namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Json extends AbstractFilter
{
    /**
     * @var int
     */
    public $priority = 100;
    
    /**
     * @inheritDoc
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new \kuiper\web\middlewares\Json();
    }
}
