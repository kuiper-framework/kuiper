<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface RpcMethodInterface
{
    /**
     * @return object|string
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getTargetClass(): string;

    /**
     * @return string
     */
    public function getServiceName(): string;

    /**
     * @return string
     */
    public function getMethodName(): string;

    /**
     * @return array
     */
    public function getArguments(): array;

    /**
     * @param array $args
     *
     * @return static
     */
    public function withArguments(array $args);

    /**
     * @return array
     */
    public function getResult(): array;

    /**
     * @param array $result
     *
     * @return static
     */
    public function withResult(array $result);

    /**
     * @return string
     */
    public function __toString(): string;
}
