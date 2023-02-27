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

use Exception;
use kuiper\rpc\RpcRequestInterface;

class RequestException extends Exception
{
    public function __construct(private readonly RpcRequestInterface $request, string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }
}
