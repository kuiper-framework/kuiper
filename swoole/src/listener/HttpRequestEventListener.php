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

namespace kuiper\swoole\listener;

use Exception;
use kuiper\event\EventListenerInterface;
use kuiper\logger\Logger;
use kuiper\swoole\event\RequestEvent;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class HttpRequestEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function __construct(private readonly RequestHandlerInterface $requestHandler)
    {
        $this->setLogger(Logger::nullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, RequestEvent::class);
        try {
            $this->logger->debug(static::TAG.'receive request');
            /* @var RequestEvent $event */
            $event->setResponse($this->requestHandler->handle($event->getRequest()));
        } catch (Exception $e) {
            $this->logger->error(static::TAG.'Uncaught exception: '.get_class($e).': '.$e->getMessage()."\n"
                .$e->getTraceAsString());
        }
    }

    public function getSubscribedEvent(): string
    {
        return RequestEvent::class;
    }
}
