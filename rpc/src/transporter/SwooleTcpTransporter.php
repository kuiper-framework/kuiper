<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\rpc\exception\ConnectFailedException;
use kuiper\rpc\exception\ErrorCode;
use kuiper\swoole\constants\ServerSetting;
use Psr\Http\Message\ResponseInterface;
use Swoole\Client;

class SwooleTcpTransporter extends AbstractTcpTransporter
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var array
     */
    protected $options = [
        ServerSetting::OPEN_LENGTH_CHECK => true,
        ServerSetting::PACKAGE_LENGTH_TYPE => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 0,
        Endpoint::CONNECT_TIMEOUT => 5.0,
        Endpoint::RECV_TIMEOUT => 5.0,
        ServerSetting::PACKAGE_MAX_LENGTH => 10485760,
    ];

    /**
     * @return Client|\Swoole\Coroutine\Client
     */
    protected function createSwooleClient()
    {
        return new Client(SWOOLE_SOCK_TCP);
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        $client = $this->createSwooleClient();
        $client->set($this->options);

        $isConnected = $client->connect(
            $this->getEndpoint()->getHost(),
            $this->getEndpoint()->getPort(),
            $this->getEndpoint()->getConnectTimeout() ?? $this->options[Endpoint::CONNECT_TIMEOUT]
        );
        if (!$isConnected) {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_CONNECT_FAILED));
        }

        return $client;
    }

    protected function destroyResource(): void
    {
        /** @var Client|null $client */
        $client = $this->getResource();
        if (null !== $client && $client->isConnected()) {
            $client->close();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(string $data): ResponseInterface
    {
        /** @var Client $client */
        $client = $this->getResource();
        if (false === $client->send($data)) {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_CONNECT_FAILED));
        }

        return $this->recv();
    }

    /**
     * @return string|false
     */
    protected function doRecv(float $timeout)
    {
        return $this->getResource()->recv();
    }

    public function recv(): ResponseInterface
    {
        $client = $this->getResource();
        if (null === $client) {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_CLOSED));
        }
        $response = $this->doRecv($this->getEndpoint()->getReceiveTimeout() ?? $this->options[Endpoint::RECV_TIMEOUT]);
        if (is_string($response) && '' !== $response) {
            return $this->createResponse($response);
        }
        if ('' === $response) {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_CLOSED));
        } elseif (SOCKET_ETIMEDOUT === $client->errCode) {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_TIMEOUT));
        } else {
            $this->onConnectionError(ErrorCode::fromValue(ErrorCode::SOCKET_RECEIVE_FAILED),
                isset($client->errCode) ? socket_strerror($client->errCode) : null);
        }
        throw new ConnectFailedException($this, 'should not arrive here');
    }
}
