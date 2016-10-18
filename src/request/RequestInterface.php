<?php
namespace kuiper\rpc\server\request;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getBody();
}
