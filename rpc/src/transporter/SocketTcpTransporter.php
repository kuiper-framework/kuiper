<?php

/** @noinspection PhpComposerExtensionStubsInspection */

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

use kuiper\rpc\exception\ConnectionException;
use kuiper\rpc\exception\ErrorCode;
use Psr\Http\Message\ResponseInterface;
use function socket_close;
use function socket_connect;
use function socket_create;
use function socket_read;
use function socket_write;

/**
 * Class SocketTcpTransporter.
 */
class SocketTcpTransporter extends AbstractTcpTransporter
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $socket) {
            $this->onConnectionError(ErrorCode::SOCKET_CREATE_FAILED);
        }
        if (!socket_connect($socket, $this->getEndpoint()->getHost(), $this->getEndpoint()->getPort())) {
            socket_close($socket);
            $this->onConnectionError(ErrorCode::SOCKET_CONNECT_FAILED);
        }

        return $socket;
    }

    /**
     * {@inheritdoc}
     */
    protected function destroyResource(): void
    {
        socket_close($this->getResource());
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(string $data): void
    {
        $socket = $this->getResource();
        if (!socket_write($socket, $data, strlen($data))) {
            $this->onConnectionError(ErrorCode::SOCKET_SEND_FAILED);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function recv(): ResponseInterface
    {
        $socket = $this->getResource();
        $time = microtime(true);
        $timeout = ($this->getEndpoint()->getReceiveTimeout() ?? 5.0) * 10000;
        $responseLength = 0;
        $response = null;
        while (true) {
            if (1000 * (microtime(true) - $time) > $timeout) {
                $this->onConnectionError(ErrorCode::SOCKET_SELECT_TIMEOUT);
            }
            // 读取最多32M的数据
            $data = socket_read($socket, 65536, PHP_BINARY_READ);

            if (empty($data)) {
                // 已经断开连接
                $this->onConnectionError(ErrorCode::SOCKET_CLOSED);
            }

            // 第一个包
            if (null === $response) {
                $response = $data;
                // 在这里从第一个包中获取总包长
                $list = unpack('Nlen', substr($data, 0, 4));
                $responseLength = $list['len'];
            } else {
                $response .= $data;
            }

            // check if all package is received
            if (strlen($response) >= $responseLength) {
                break;
            }
        }

        return $this->createResponse($response);
    }
}
