<?php
namespace kuiper\web\middlewares;

use kuiper\web\exception\MethodNotAllowedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestMethod
{
    /**
     * @var array
     */
    private $methods;

    public function __construct(array $methods)
    {
        $this->setMethods($methods);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!in_array(strtoupper($request->getMethod()), $this->methods)) {
            throw new MethodNotAllowedException($this->methods, $request, $response);
        }
        return $next($request, $response);
    }

    protected function setMethods($methods)
    {
        $this->methods = $methods;
    }
}
