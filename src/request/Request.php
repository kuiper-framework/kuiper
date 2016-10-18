<?php
namespace kuiper\rpc\server\request;

class Request implements RequestInterface
{
    private $body;

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
}
