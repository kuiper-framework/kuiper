<?php
namespace kuiper\rpc\server\response;

interface ResponseInterface
{
    /**
     * @return string
     */
    public function getBody();
}
