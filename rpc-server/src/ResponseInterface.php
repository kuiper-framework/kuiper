<?php

namespace kuiper\rpc\server;

interface ResponseInterface extends MessageInterface
{
    public function getResult();

    public function withResult($result);
}
