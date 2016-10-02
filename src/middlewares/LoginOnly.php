<?php
namespace kuiper\web\middlewares;

use kuiper\web\security\AuthInterface;
use kuiper\web\exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class LoginOnly
{
    /**
     * @var AuthInterface
     */
    private $auth;

    public function __construct(AuthInterface $auth)
    {
        $this->auth = $auth;
    }
    
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->auth->isGuest()) {
            throw new UnauthorizedException($request, $response);
        }
        return $next($request, $response);
    }
}
