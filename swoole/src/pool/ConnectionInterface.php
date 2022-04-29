<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

interface ConnectionInterface
{
    /**
     * Gets the resource object.
     *
     * @return mixed
     */
    public function getResource(): mixed;

    /**
     * Gets the connection id.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Gets the created time.
     *
     * @return float
     */
    public function getCreatedAt(): float;

    /**
     * Close the connection.
     */
    public function close(): void;
}
