<?php

namespace kuiper\rpc;

interface ResponseInterface extends MessageInterface
{
    /**
     * Gets the rpc result.
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Returns an instance with the rpc results.
     *
     * @param $result
     *
     * @return static
     */
    public function withResult($result);
}
