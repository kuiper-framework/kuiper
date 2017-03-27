<?php

namespace kuiper\rpc;

interface ResponseInterface extends MessageInterface
{
    public function getResult();

    public function withResult($result);
}
