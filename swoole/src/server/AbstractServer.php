<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\helper\Properties;
use kuiper\swoole\event\ServerEventFactory;
use kuiper\swoole\ServerConfig;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractServer implements ServerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const TAG = '['.__CLASS__.'] ';

    /**
     * @var ServerConfig
     */
    private $serverConfig;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ServerEventFactory
     */
    private $serverEventFactory;

    public function __construct(ServerConfig $serverConfig, EventDispatcherInterface $eventDispatcher)
    {
        $this->serverConfig = $serverConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->serverEventFactory = new ServerEventFactory();
    }

    protected function dispatch(string $eventName, array $args): void
    {
        array_unshift($args, $this);
        $event = $this->serverEventFactory->create($eventName, $args);
        if ($event) {
            $this->logger->debug(self::TAG."dispatch event $eventName using ".get_class($event));
            $this->getEventDispatcher()->dispatch($event);
        } else {
            $this->logger->debug(self::TAG."unhandled event $eventName");
        }
    }

    public function getServerConfig(): ServerConfig
    {
        return $this->serverConfig;
    }

    public function getSettings(): Properties
    {
        return $this->serverConfig->getSettings();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
