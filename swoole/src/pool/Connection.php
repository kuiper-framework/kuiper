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

class Connection implements ConnectionInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var mixed
     */
    private $resource;

    /**
     * @var float
     */
    private $createdAt;

    public function __construct(int $id, $resource)
    {
        $this->id = $id;
        $this->resource = $resource;
        $this->createdAt = microtime(true);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getCreatedAt(): float
    {
        return $this->createdAt;
    }

    public function close(): void
    {
        if (is_object($this->resource)) {
            foreach (['close', 'disconnect'] as $method) {
                if (method_exists($this->resource, $method)) {
                    try {
                        /** @phpstan-ignore-next-line */
                        $this->resource->$method();
                    } catch (\Exception $e) {
                        // noOps
                    }
                }
            }
        }
        unset($this->resource);
    }
}
