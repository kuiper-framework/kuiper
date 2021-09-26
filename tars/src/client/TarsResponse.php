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

namespace kuiper\tars\client;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\tars\core\TarsResponseInterface;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\ResponseInterface;

class TarsResponse extends RpcResponse implements TarsResponseInterface
{
    /**
     * @var ResponsePacket
     */
    private $packet;

    public function __construct(RpcRequestInterface $request, ResponseInterface $response, ResponsePacket $packet)
    {
        parent::__construct($request, $response);
        $this->packet = $packet;
    }

    public function getResponsePacket(): ResponsePacket
    {
        return $this->packet;
    }
}
