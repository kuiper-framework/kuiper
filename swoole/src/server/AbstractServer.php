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

namespace kuiper\swoole\server;

use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use kuiper\helper\Properties;
use kuiper\swoole\event\AbstractServerEvent;
use kuiper\swoole\event\MessageInterface;
use kuiper\swoole\event\ServerEventFactory;
use kuiper\swoole\ServerConfig;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractServer implements ServerInterface, LoggerAwareInterface, EventDispatcherAwareInterface
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    private readonly ServerEventFactory $serverEventFactory;

    public function __construct(private readonly ServerConfig $serverConfig)
    {
        $this->serverEventFactory = new ServerEventFactory();
    }

    public function dispatch(string $eventName, array $args): ?AbstractServerEvent
    {
        array_unshift($args, $this);
        $event = $this->serverEventFactory->create($eventName, $args);
        if (null !== $event) {
            $this->logger->debug(static::TAG."dispatch event $eventName using ".get_class($event));

            /* @noinspection PhpIncompatibleReturnTypeInspection */
            return $this->getEventDispatcher()->dispatch($event);
        }

        $this->logger->debug(static::TAG."unhandled event $eventName");

        return null;
    }

    public function getServerConfig(): ServerConfig
    {
        return $this->serverConfig;
    }

    public function getServerName(): string
    {
        return $this->getServerConfig()->getServerName();
    }

    public function getSettings(): Properties
    {
        return $this->serverConfig->getSettings();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function sendMessage(MessageInterface $message, int $workerId): void
    {
    }

    public function sendMessageToAll(MessageInterface $message): void
    {
    }
}
