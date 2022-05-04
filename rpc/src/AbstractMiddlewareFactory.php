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

namespace kuiper\rpc;

abstract class AbstractMiddlewareFactory implements MiddlewareFactoryInterface
{
    /**
     * @param int $priority
     */
    public function __construct(private readonly int $priority = 1024)
    {
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
