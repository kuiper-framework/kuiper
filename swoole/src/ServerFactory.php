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

use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use kuiper\logger\Logger;
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
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ServerFactory implements LoggerAwareInterface, EventDispatcherAwareInterface
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    private bool $phpServerEnabled = false;

    private ?HttpMessageFactoryHolder $httpMessageFactoryHolder = null;

    private ?SwooleRequestBridgeInterface $swooleRequestBridge = null;

    private ?SwooleResponseBridgeInterface $swooleResponseBridge = null;

    public function __construct()
    {
        $this->setLogger(Logger::nullLogger());
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

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        if (null === $this->httpMessageFactoryHolder) {
            $this->checkDiactoros();
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
            $this->checkDiactoros();
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
        if (!$this->isPhpServerEnabled()) {
            return $this->createSwooleServer($serverConfig);
        }

        return match ($serverConfig->getPort()->getServerType()) {
            ServerType::TCP => $this->createTcpServer($serverConfig),
            ServerType::HTTP => $this->createHttpServer($serverConfig),
            default => $this->createSwooleServer($serverConfig),
        };
    }

    private function createTcpServer(ServerConfig $serverConfig): SelectTcpServer
    {
        $server = new SelectTcpServer($serverConfig);
        $server->setLogger($this->logger);
        $server->setEventDispatcher($this->getEventDispatcher());

        return $server;
    }

    private function createHttpServer(ServerConfig $serverConfig): HttpServer
    {
        $server = new HttpServer($serverConfig, $this->getHttpMessageFactoryHolder());
        $server->setLogger($this->logger);
        $server->setEventDispatcher($this->getEventDispatcher());

        return $server;
    }

    private function createSwooleServer(ServerConfig $serverConfig): SwooleServer
    {
        $server = new SwooleServer($serverConfig);
        $server->setLogger($this->logger);
        $server->setEventDispatcher($this->getEventDispatcher());
        if ($serverConfig->getPort()->isHttpProtocol()) {
            $server->setHttpMessageFactoryHolder($this->getHttpMessageFactoryHolder());
            $server->setSwooleRequestBridge($this->getSwooleRequestBridge());
            $server->setSwooleResponseBridge($this->getSwooleResponseBridge());
        }

        return $server;
    }

    private function checkDiactoros(): void
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
            throw new RuntimeException("Cannot load class {$className}. Consider compose require {$package}");
        }
    }
}
