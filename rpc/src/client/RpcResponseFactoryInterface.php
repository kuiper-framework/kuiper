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

namespace kuiper\rpc\client;

use Exception;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use Psr\Http\Message\ResponseInterface;

interface RpcResponseFactoryInterface
{
    /**
     * Creates the rpc response from http response.
     *
     * @throws Exception
     */
    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface;
}
