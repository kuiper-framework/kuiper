<?php

namespace kuiper\web\exception;

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

    public function getResponse()
    {
        return parent::getResponse()
            ->withHeader("location", $this->url);
    }
}
