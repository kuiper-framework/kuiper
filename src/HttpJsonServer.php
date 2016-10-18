<?php
namespace kuiper\rpc\server;

use kuiper\rpc\server\request\RequestFactory;
use kuiper\rpc\server\response\Error;
use kuiper\rpc\server\exception\InvalidRequestException;
use RuntimeException;

class HttpJsonServer
{
    /**
     * @var array
     */
    private $options = [];

    /**
     * @var ServerInterface
     */
    private $server;

    public function __construct(ServerInterface $server, array $options = [])
    {
        $this->server = $server;
        $this->options = $options;
    }
    
    /**
     * @inheritDoc
     */
    public function start()
    {
        if (PHP_SAPI === 'cli-server') {
            $this->startBuiltinServer();
        } elseif (PHP_SAPI === 'cli') {
            $this->startSwooleServer();
        } else {
            throw new RuntimeException("Cannot start server in current sapi " . PHP_SAPI);
        }
    }

    private function startSwooleServer()
    {
        if (!extension_loaded('swoole')) {
            throw new RuntimeException("extension swoole is required to start server");
        }
        if (!ini_get('swoole.use_namespace')) {
            throw new RuntimeException("swoole.use_namespace must turn on");
        }
        $options = array_merge([
            'host' => 'localhost',
            'port' => '8080',
            'worker_num' => 2
        ], $this->getCommandLineOptions(), $this->options);
        $server = new \Swoole\Http\Server($options['host'], $options['port']);
        $server->set($options);
        $server->on('request', [$this, 'handleSwooleRequest']);
        $server->start();
    }

    private function getCommandLineOptions()
    {
        $options = getopt('h', ['port:', 'host:']);
        if (isset($options['h'])) {
            $this->showUsage();
        }
        return $options;
    }

    private function showUsage($exit = 0, $msg = '')
    {
        if ($msg) {
            echo $msg, "\n\n";
        }
        echo sprintf("Usage: php %s \[options\]\n\n", $_SERVER['PHP_SELF']),
            "Options:\n",
            "   --host host Server listening host (default localhost)\n",
            "   --port port Server listening port (default 8080)\n",
            "   -h          Show this message\n";
        exit($exit);
    }

    public function handleSwooleRequest($request, $response)
    {
        $response->header('Content-Type', 'application/json');
        if ($request->server['request_method'] !== 'POST') {
            $response->end($this->getErrorResponse());
        } else {
            $response->end(
                $body = $this->server->handle(RequestFactory::fromString($request->rawContent()))
                ->getBody()
            );
        }
    }

    private function startBuiltinServer()
    {
        header('Content-Type: application/json');
        try {
            echo $this->server->handle(RequestFactory::fromGlobals())
                ->getBody();
        } catch (InvalidRequestException $e) {
            echo $this->getErrorResponse();
        }
    }

    private function getErrorResponse()
    {
        return json_encode([
            "error" => [
                'code' => Error::ERROR_INVALID_REQUEST,
                'message' => 'Invalid Request'
            ]
        ]);
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
