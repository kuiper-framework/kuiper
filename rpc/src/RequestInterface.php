<?php

namespace kuiper\rpc;

interface RequestInterface extends MessageInterface
{
    /**
     * Gets the rpc method name.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Returns an instance with the rpc method name.
     *
     * @param string $method
     *
     * @return static
     */
    public function withMethod($method);

    /**
     * Returns the rpc parameters.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Returns an instance with the rpc parameters.
     *
     * @param array $parameters
     *
     * @return static
     */
    public function withParameters(array $parameters);
}
