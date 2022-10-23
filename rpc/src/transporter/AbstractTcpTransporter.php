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

use kuiper\logger\Logger;
use kuiper\rpc\exception\CannotResolveEndpointException;
use kuiper\rpc\exception\CommunicationException;
use kuiper\rpc\exception\ConnectFailedException;
use kuiper\rpc\exception\ConnectionClosedException;
use kuiper\rpc\exception\ConnectionException;
use kuiper\rpc\exception\ErrorCode;
use kuiper\rpc\exception\TimedOutException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractTcpTransporter implements TransporterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    private const ERROR_EXCEPTIONS = [
        ErrorCode::SOCKET_CLOSED => ConnectionClosedException::class,
        ErrorCode::SOCKET_TIMEOUT => TimedOutException::class,
        ErrorCode::SOCKET_CONNECT_FAILED => ConnectFailedException::class,
        ErrorCode::SOCKET_RECEIVE_FAILED => ConnectFailedException::class,
    ];

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @var Endpoint|null
     */
    private ?Endpoint $endpoint = null;

    /**
     * @var mixed
     */
    private mixed $resource = null;

    /**
     * AbstractTcpTransporter constructor.
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        array $options = [],
        LoggerInterface $logger = null)
    {
        $this->setOptions($options);
        $this->setLogger($logger ?? Logger::nullLogger());
    }

    public function getEndpoint(): Endpoint
    {
        return $this->endpoint;
    }

    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
        if (isset($this->options['endpoint'])) {
            $endpoint = $this->options['endpoint'];
            if (is_string($endpoint)) {
                $endpoint = Endpoint::fromString($endpoint);
            }
            $this->endpoint = $endpoint;
        }
    }

    /**
     * Disconnects from the server and destroys the underlying resource when
     * PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->close();
    }

    public function isOpen(): bool
    {
        return isset($this->resource);
    }

    /**
     * @throws CommunicationException|CannotResolveEndpointException
     */
    public function open(?Endpoint $endpoint): void
    {
        $this->resolveEndpoint($endpoint);
        if (!$this->isOpen()) {
            $this->resource = $this->createResource();
        }
    }

    public function close(): void
    {
        if (isset($this->resource)) {
            $this->destroyResource();
            $this->resource = null;
        }
    }

    /**
     * @return mixed
     */
    protected function getResource()
    {
        return $this->resource;
    }

    public function createSession(RequestInterface $request): Session
    {
        $endpoint = Endpoint::fromUri($request->getUri());
        $this->open($endpoint->getPort() > 0 ? $endpoint : null);
        $this->doSend((string) $request->getBody());

        return new TcpSession($this);
    }

    /**
     * @throws CannotResolveEndpointException
     */
    protected function resolveEndpoint(?Endpoint $endpoint): void
    {
        if (null !== $endpoint) {
            if (null !== $this->endpoint) {
                if (!$this->endpoint->equals($endpoint)) {
                    $this->close();
                    $this->endpoint = $endpoint->merge($this->endpoint);
                }
            } else {
                $this->endpoint = $endpoint;
            }
        }
        if (null === $this->endpoint) {
            throw new CannotResolveEndpointException('endpoint is empty');
        }
    }

    protected function createResponse(string $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * Helper method to handle connection errors.
     *
     * @throws ConnectionException
     */
    protected function onConnectionError(int $errorCode, string $message = null): void
    {
        $exception = $this->createException($errorCode, $message);
        $this->close();

        throw $exception;
    }

    protected function createException(int $errorCode, ?string $message): ConnectionException
    {
        $message = ($message ?? ErrorCode::getMessage($errorCode)).'(address='.$this->endpoint.')';
        if (isset(self::ERROR_EXCEPTIONS[$errorCode])) {
            $class = self::ERROR_EXCEPTIONS[$errorCode];
            $exception = new $class($this, $message, $errorCode);
        } else {
            $exception = new ConnectionException($this, $message, $errorCode);
        }

        return $exception;
    }

    /**
     * Creates the underlying resource used to communicate with server.
     *
     * @return mixed
     *
     * @throws CommunicationException
     */
    abstract protected function createResource();

    /**
     * Destroy the underlying resource.
     */
    abstract protected function destroyResource(): void;

    /**
     * @throws CommunicationException
     */
    abstract protected function doSend(string $data): void;

    /**
     * @return ResponseInterface
     *
     * @throws ConnectionException
     */
    abstract public function recv(): ResponseInterface;
}
