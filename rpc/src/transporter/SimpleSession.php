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

use Psr\Http\Message\ResponseInterface;

class SimpleSession implements Session
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function close(): void
    {
    }

    public function recv(): ResponseInterface
    {
        return $this->response;
    }
}
