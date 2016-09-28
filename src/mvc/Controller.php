<?php
namespace chaozhuo\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use chaozhuo\web\exception\NotFoundException;
use chaozhuo\web\exception\AccessDeniedException;
use chaozhuo\web\exception\UnauthorizedException;

abstract class Controller implements ControllerInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @inheritDoc
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function initialize()
    {
    }

    protected function notFound($message = null)
    {
        throw new NotFoundException($this->request, $this->response, $message);
    }

    protected function accessDenied()
    {
        throw new AccessDeniedException($this->request, $this->response);
    }

    protected function authorizationRequired()
    {
        throw new UnauthorizedException($this->request, $this->response);
    }
}
