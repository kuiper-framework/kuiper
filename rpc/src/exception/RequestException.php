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

class RequestException extends \Exception
{
    /**
     * @var RpcRequestInterface
     */
    private $request;

    /**
     * InvalidRequestException constructor.
     */
    public function __construct(RpcRequestInterface $request, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->request = $request;
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }
}
