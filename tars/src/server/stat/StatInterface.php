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

namespace kuiper\tars\server\stat;

use kuiper\rpc\RpcResponseInterface;

interface StatInterface
{
    public function success(RpcResponseInterface $response, int $responseTime): void;

    public function fail(RpcResponseInterface $response, int $responseTime): void;

    public function timedOut(RpcResponseInterface $response, int $responseTime): void;

    /**
     * @return StatEntry[]
     */
    public function flush(): array;
}
