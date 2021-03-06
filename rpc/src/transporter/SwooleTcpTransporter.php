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

use kuiper\rpc\exception\ConnectFailedException;
use kuiper\rpc\exception\ErrorCode;
use kuiper\swoole\constants\ClientSettings;
use Psr\Http\Message\ResponseInterface;
use Swoole\Client;

class SwooleTcpTransporter extends AbstractTcpTransporter
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var array
     */
    protected $options = [
        ClientSettings::OPEN_LENGTH_CHECK => true,
        ClientSettings::PACKAGE_LENGTH_TYPE => 'N',
        ClientSettings::PACKAGE_LENGTH_OFFSET => 0,
        ClientSettings::PACKAGE_BODY_OFFSET => 0,
        ClientSettings::CONNECT_TIMEOUT => 5.0,
        ClientSettings::RECV_TIMEOUT => 5.0,
        ClientSettings::PACKAGE_MAX_LENGTH => 10485760,
    ];

    /**
     * @var array
     */
    protected $clientOptions = [];

    public function setOptions(array $options): void
    {
        parent::setOptions($options);
        foreach ($this->options as $name => $value) {
            if (ClientSettings::hasValue($name)) {
                $this->clientOptions[$name] = $value;
            }
        }
    }

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
        $client->set($this->clientOptions);

        $isConnected = @$client->connect(
            $this->getEndpoint()->getHost(),
            $this->getEndpoint()->getPort(),
            $this->getEndpoint()->getConnectTimeout() ?? $this->options[ClientSettings::CONNECT_TIMEOUT]
        );
        if (!$isConnected) {
            if ($client->isConnected()) {
                $client->close();
            }
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
        $response = $this->doRecv($this->getEndpoint()->getReceiveTimeout() ?? $this->options[ClientSettings::RECV_TIMEOUT]);
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
