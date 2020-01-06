<?php

namespace kuiper\web\middlewares;

use kuiper\web\exception\CsrfTokenException;
use kuiper\web\exception\MethodNotAllowedException;
use kuiper\web\security\CsrfTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsrfToken
{
    private static $ALLOWED_METHODS = ['PUT', 'POST', 'DELETE'];

    /**
     * @var bool is repeat request ok?
     */
    private $repeatOk;

    /**
     * @var CsrfTokenInterface
     */
    private $csrfToken;

    public function __construct(CsrfTokenInterface $csrfToken, $repeatOk = true)
    {
        $this->csrfToken = $csrfToken;
        $this->repeatOk = $repeatOk;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!in_array($request->getMethod(), self::$ALLOWED_METHODS)) {
            throw new MethodNotAllowedException(self::$ALLOWED_METHODS);
        }
        if ($this->csrfToken->check($request, $destroy = !$this->repeatOk)) {
            return $next($request, $response);
        } else {
            throw new CsrfTokenException();
        }
    }
}
