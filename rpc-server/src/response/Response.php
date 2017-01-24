<?php
namespace kuiper\rpc\server\response;

class Response implements ResponseInterface
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
