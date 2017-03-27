<?php

namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;

class RedirectException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 302;

    /**
     * @var string
     */
    protected $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse(ResponseInterface $response)
    {
        return parent::setResponse($response->withHeader('location', $this->url));
    }

    public function getUrl()
    {
        return $this->url;
    }
}
