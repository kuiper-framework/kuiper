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
    /**
     * @var RpcRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * BadResponseException constructor.
     */
    public function __construct(RpcRequestInterface $request, ResponseInterface $response, \Throwable $prev = null)
    {
        parent::__construct('Cannot parse response', 0, $prev);
        $this->request = $request;
        $this->response = $response;
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
