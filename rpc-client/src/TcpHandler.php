<?php

namespace kuiper\rpc\client;

use kuiper\rpc\client\exception\ConnectionException;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

class TcpHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $servers;

    /**
     * @var array
     */
    private $options = [
        'timeout' => 5,
    ];

    /**
     * @var resource
     */
    private $connection;

    public function __construct(array $servers, array $options = [])
    {
        $this->servers = $servers;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(RequestInterface $request, ResponseInterface $response)
    {
        if (fwrite($this->getConnection(), (string) $request->getBody()) !== $request->getBody()->getSize()) {
            throw new ConnectionException('Cannot send data');
        }

        $response->getBody()->write(fgets($this->connection));

        return $response;
    }

    protected function getConnection()
    {
        if ($this->connection === null) {
            $address = $this->servers[array_rand($this->servers)];
            $this->connection = stream_socket_client($address, $errorCode, $errorMessage);
            if (!$this->connection) {
                throw new ConnectionException("can not connect to $address , $errorCode:$errorMessage");
            }
            stream_set_blocking($this->connection, true);
            stream_set_timeout($this->connection, $this->options['timeout']);
        }

        return $this->connection;
    }
}
