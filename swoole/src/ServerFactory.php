<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\constants\ServerType;
use kuiper\swoole\http\DiactorosSwooleRequestBridge;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\server\HttpMessageFactoryHolder;
use kuiper\swoole\server\HttpServer;
use kuiper\swoole\server\SelectTcpServer;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\server\SwooleServer;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ServerFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    private $phpServerEnabled = false;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var HttpMessageFactoryHolder
     */
    private $httpMessageFactoryHolder;

    /**
     * @var SwooleRequestBridgeInterface
     */
    private $swooleRequestBridge;

    /**
     * @var SwooleResponseBridgeInterface
     */
    private $swooleResponseBridge;

    /**
     * ServerFactory constructor.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->setLogger($logger ?? new NullLogger());
    }

    public function isPhpServerEnabled(): bool
    {
        return $this->phpServerEnabled;
    }

    public function enablePhpServer(bool $enable = true): ServerFactory
    {
        $this->phpServerEnabled = $enable;

        return $this;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        if (!$this->eventDispatcher) {
            $this->checkSymfonyEventDispatcher();
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): ServerFactory
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        if (!$this->httpMessageFactoryHolder) {
            $this->checkLaminasDiactoros();
            $httpMessageFactoryHolder = new HttpMessageFactoryHolder();
            $httpMessageFactoryHolder->setUriFactory(new UriFactory());
            $httpMessageFactoryHolder->setResponseFactory(new ResponseFactory());
            $httpMessageFactoryHolder->setServerRequestFactory(new ServerRequestFactory());
            $httpMessageFactoryHolder->setStreamFactory(new StreamFactory());
            $httpMessageFactoryHolder->setUploadFileFactory(new UploadedFileFactory());
            $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;
        }

        return $this->httpMessageFactoryHolder;
    }

    public function setHttpMessageFactoryHolder(HttpMessageFactoryHolder $httpMessageFactoryHolder): ServerFactory
    {
        $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;

        return $this;
    }

    public function getSwooleRequestBridge(): SwooleRequestBridgeInterface
    {
        if (!$this->swooleRequestBridge) {
            $this->checkLaminasDiactoros();
            $this->swooleRequestBridge = new DiactorosSwooleRequestBridge();
        }

        return $this->swooleRequestBridge;
    }

    public function setSwooleRequestBridge(SwooleRequestBridgeInterface $swooleRequestBridge): ServerFactory
    {
        $this->swooleRequestBridge = $swooleRequestBridge;

        return $this;
    }

    public function getSwooleResponseBridge(): SwooleResponseBridgeInterface
    {
        if (!$this->swooleResponseBridge) {
            $this->swooleResponseBridge = new SwooleResponseBridge();
        }

        return $this->swooleResponseBridge;
    }

    public function setSwooleResponseBridge(SwooleResponseBridgeInterface $swooleResponseBridge): ServerFactory
    {
        $this->swooleResponseBridge = $swooleResponseBridge;

        return $this;
    }

    public function create(ServerConfig $serverConfig): ServerInterface
    {
        if (!$this->phpServerEnabled) {
            return $this->createSwooleServer($serverConfig);
        }
        switch ($serverConfig->getPort()->getServerType()->value) {
            case ServerType::TCP:
                return $this->createTcpServer($serverConfig);
            case ServerType::HTTP:
                return $this->createHttpServer($serverConfig);
            default:
                return $this->createSwooleServer($serverConfig);
        }
    }

    private function createTcpServer(ServerConfig $serverConfig): SelectTcpServer
    {
        return new SelectTcpServer($serverConfig, $this->getEventDispatcher(), $this->logger);
    }

    private function createHttpServer(ServerConfig $serverConfig): HttpServer
    {
        $httpServer = new HttpServer($serverConfig, $this->getEventDispatcher(), $this->logger);
        $httpServer->setHttpMessageFactoryHolder($this->getHttpMessageFactoryHolder());

        return $httpServer;
    }

    private function createSwooleServer(ServerConfig $serverConfig): SwooleServer
    {
        SwooleServer::check();
        $swooleServer = new SwooleServer($serverConfig, $this->getEventDispatcher(), $this->logger);
        if ($serverConfig->getPort()->getServerType()->isHttpProtocol()) {
            $swooleServer->setHttpMessageFactoryHolder($this->getHttpMessageFactoryHolder());
            $swooleServer->setSwooleRequestBridge($this->getSwooleRequestBridge());
            $swooleServer->setSwooleResponseBridge($this->getSwooleResponseBridge());
        }

        return $swooleServer;
    }

    private function checkLaminasDiactoros(): void
    {
        $this->checkClassExists(EventDispatcher::class, 'symfony/event-dispatcher');
    }

    private function checkSymfonyEventDispatcher(): void
    {
        $this->checkClassExists(RequestFactory::class, 'laminas/laminas-diactoros');
    }

    private function checkClassExists(string $className, string $package): void
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Cannot load class {$className}. "."Consider compose require {$package}");
        }
    }
}
