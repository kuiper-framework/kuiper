<?php
namespace kuiper\rpc\client;

use kuiper\rpc\client\exception\ConnectionException;
use kuiper\serializer\NormalizerInterface;
use kuiper\annotations\DocReaderInterface;

class TcpJsonClient extends AbstractJsonClient
{
    /**
     * @var array
     */
    private $servers;

    /**
     * @var array
     */
    private $options = [
        'timeout' => 5
    ];

    /**
     * @var resource
     */
    private $connection;
    
    public function __construct(
        array $servers,
        NormalizerInterface $normalizer,
        DocReaderInterface $docReader,
        array $map = [],
        array $options = []
    ) {
        $this->servers = $servers;
        $this->options = array_merge($this->options, $options);
        parent::__construct($normalizer, $docReader, $map);
    }

    protected function getConnection()
    {
        if ($this->connection === null) {
            $address = $this->servers[array_rand($this->servers)];
            $this->connection = stream_socket_client($address, $err_no, $err_msg);
            if (!$this->connection) {
                throw new ConnectionException("can not connect to $address , $err_no:$err_msg");
            }
            stream_set_blocking($this->connection, true);
            stream_set_timeout($this->connection, $this->getOption('timeout'));
        }
        return $this->connection;
    }

    public function sendRequest($requestBody)
    {
        $requestBody = str_replace("\n", " ", $requestBody) . "\n";
        if (fwrite($this->getConnection(), $requestBody) !== strlen($requestBody)) {
            throw new ConnectionException("Cannot send data");
        }

        return fgets($this->connection);
    }

    protected function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function getServers()
    {
        return $this->servers;
    }
}
