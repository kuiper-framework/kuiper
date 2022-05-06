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

namespace kuiper\rpc\transporter;

use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\RequestInterface;

class PooledTransporter implements TransporterInterface
{
    public function __construct(private readonly PoolInterface $pool)
    {
    }

    public function close(): void
    {
        $this->pool->close();
    }

    public function createSession(RequestInterface $request): Session
    {
        $conn = $this->pool->take();
        $session = $conn->createSession($request);

        return new PooledSession($this->pool, $conn, $session);
    }
}
