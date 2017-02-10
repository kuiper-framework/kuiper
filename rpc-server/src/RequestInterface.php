<?php

namespace kuiper\rpc\server;

interface RequestInterface extends MessageInterface
{
    /**
     * Gets the parsed parameters of the body.
     *
     * @return array
     */
    public function getMethod();

    public function withMethod($method);

    public function getParameters();

    public function withParameters(array $parameters);
}
