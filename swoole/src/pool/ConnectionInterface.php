<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


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
