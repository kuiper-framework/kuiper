<?php
namespace kuiper\web;

use kuiper\web\exception\AccessDeniedException;
use kuiper\web\exception\NotFoundException;
use kuiper\web\exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    public function getRequest()
    {
        return $this->request;
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
