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

namespace kuiper\swoole;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\http\DiactorosSwooleRequestBridge;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
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
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    /**
     * @var HttpMessageFactoryHolder|null
     */
    private $httpMessageFactoryHolder;

    /**
     * @var SwooleRequestBridgeInterface|null
     */
    private $swooleRequestBridge;

    /**
     * @var SwooleResponseBridgeInterface|null
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

    public function enablePhpServer(bool $enable = true): self
    {
        $this->phpServerEnabled = $enable;

        return $this;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        if (null === $this->eventDispatcher) {
            $this->checkSymfonyEventDispatcher();
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param EventListenerInterface|string        $event
     * @param EventListenerInterface|callable|null $listener
     *
     * @return $this
     */
    public function addEventListener($event, $listener = null): self
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getEventDispatcher();
        if ($event instanceof EventListenerInterface) {
            $eventDispatcher->addListener($event->getSubscribedEvent(), $event);
        } else {
            $eventDispatcher->addListener($event, $listener);
        }

        return $this;
    }

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        if (null === $this->httpMessageFactoryHolder) {
            $this->checkLaminasDiactoros();
            $this->httpMessageFactoryHolder = new HttpMessageFactoryHolder(
                new ServerRequestFactory(),
                new ResponseFactory(),
                new StreamFactory(),
                new UriFactory(),
                new UploadedFileFactory()
            );
        }

        return $this->httpMessageFactoryHolder;
    }

    public function setHttpMessageFactoryHolder(HttpMessageFactoryHolder $httpMessageFactoryHolder): self
    {
        $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;

        return $this;
    }

    public function getSwooleRequestBridge(): SwooleRequestBridgeInterface
    {
        if (null === $this->swooleRequestBridge) {
            $this->checkLaminasDiactoros();
            $this->swooleRequestBridge = new DiactorosSwooleRequestBridge();
        }

        return $this->swooleRequestBridge;
    }

    public function setSwooleRequestBridge(SwooleRequestBridgeInterface $swooleRequestBridge): self
    {
        $this->swooleRequestBridge = $swooleRequestBridge;

        return $this;
    }

    public function getSwooleResponseBridge(): SwooleResponseBridgeInterface
    {
        if (null === $this->swooleResponseBridge) {
            $this->swooleResponseBridge = new SwooleResponseBridge();
        }

        return $this->swooleResponseBridge;
    }

    public function setSwooleResponseBridge(SwooleResponseBridgeInterface $swooleResponseBridge): self
    {
        $this->swooleResponseBridge = $swooleResponseBridge;

        return $this;
    }

    public function create(ServerConfig $serverConfig): ServerInterface
    {
        if (!$this->phpServerEnabled) {
            return $this->createSwooleServer($serverConfig);
        }
        switch ($serverConfig->getPort()->getServerType()) {
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
        $swooleServer = new SwooleServer($serverConfig, $this->getEventDispatcher(), $this->logger);
        if ($serverConfig->getPort()->isHttpProtocol()) {
            $swooleServer->setHttpMessageFactoryHolder($this->getHttpMessageFactoryHolder());
            $swooleServer->setSwooleRequestBridge($this->getSwooleRequestBridge());
            $swooleServer->setSwooleResponseBridge($this->getSwooleResponseBridge());
        }

        return $swooleServer;
    }

    private function checkLaminasDiactoros(): void
    {
        $this->checkClassExists(RequestFactory::class, 'laminas/laminas-diactoros');
    }

    private function checkSymfonyEventDispatcher(): void
    {
        $this->checkClassExists(EventDispatcher::class, 'symfony/event-dispatcher');
    }

    private function checkClassExists(string $className, string $package): void
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Cannot load class {$className}. "."Consider compose require {$package}");
        }
    }
}
