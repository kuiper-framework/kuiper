<?php
namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use kuiper\web\exception\NotFoundException;
use kuiper\web\exception\MethodNotAllowedException;
use kuiper\web\exception\AccessDeniedException;
use kuiper\web\exception\UnauthorizedException;
use kuiper\web\exception\BadRequestException;

class DefaultErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    private static $EXCEPTION_STATUS_CODES = [
        BadRequestException::class       => 400,
        UnauthorizedException::class     => 401,
        AccessDeniedException::class     => 403,
        NotFoundException::class         => 404,
        MethodNotAllowedException::class => 405
    ];

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    protected function getExceptionStatusCode($e)
    {
        $class = get_class($e);
        if (isset(self::$EXCEPTION_STATUS_CODES[$class])) {
            return self::$EXCEPTION_STATUS_CODES[$class];
        } else {
            return 500;
        }
    }
    
    public function handle($e)
    {
        trigger_error(sprintf("Uncaught exception %s %s:\n%s", get_class($e), $e->getMessage(), $e->getTraceAsString()));
        return $this->getResponse()->withStatus($this->getExceptionStatusCode($e));
    }
}
