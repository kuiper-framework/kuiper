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

namespace kuiper\rpc\exception;

use kuiper\rpc\RpcRequestInterface;
use Psr\Http\Message\ResponseInterface;

class BadResponseException extends \Exception
{
    public function __construct(
        private readonly RpcRequestInterface $request,
        private readonly ResponseInterface $response, \Throwable $prev = null)
    {
        parent::__construct('Cannot parse response', 0, $prev);
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
