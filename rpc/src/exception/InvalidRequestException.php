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
use Throwable;

class InvalidRequestException extends RequestException
{
    public function __construct(private readonly RpcRequestInterface $request, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }
}
