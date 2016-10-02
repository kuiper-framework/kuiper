<?php
namespace kuiper\web\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use kuiper\web\security\PermissionCheckerInterface;
use kuiper\web\exception\UnauthorizedException;
use kuiper\web\exception\AccessDeniedException;
use InvalidArgumentException;

class Acl
{
    /**
     * @var PermissionCheckerInterface
     */
    private $checker;

    /**
     * @var array<string>
     */
    private $resources;
    
    public function __construct(PermissionCheckerInterface $checker, $resources)
    {
        $this->checker = $checker;
        if (is_string($resources)) {
            $resources = [$resources];
        }
        $this->resources = $resources;
    }
    
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$this->checkPermission()) {
            throw new AccessDeniedException($request, $response);
        }
        return $next($request, $response);
    }

    private function checkPermission()
    {
        foreach ($this->resources as $resource) {
            if (!$this->checker->check($resource)) {
                return false;
            }
        }
        return true;
    }
}
