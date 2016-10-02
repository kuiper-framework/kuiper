<?php
namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;
use kuiper\web\middlewares;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class CsrfToken extends AbstractFilter
{
    /**
     * @var boolean
     */
    public $repeatOk = false;
    
    /**
     * @inheritDoc
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new middlewares\CsrfToken($container->get('security'), $this->repeatOk);
    }
}
