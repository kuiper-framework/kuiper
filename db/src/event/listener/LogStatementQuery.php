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

namespace kuiper\db\event\listener;

use kuiper\db\event\StatementQueriedEvent;
use kuiper\db\Statement;
use kuiper\event\attribute\EventListener;
use kuiper\event\EventListenerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

#[EventListener]
class LogStatementQuery implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, StatementQueriedEvent::class);
        /** @var StatementQueriedEvent $event */
        $e = $event->getException();
        /** @var Statement $stmt */
        $stmt = $event->getStatement();
        $time = 1000 * (microtime(true) - $stmt->getStartTime());
        if (null === $e) {
            $level = ($time > 1000) ? 'warning' : 'debug';
            $message = 'query';
        } else {
            $level = 'error';
            $message = 'fail query because '.$e->getMessage();
        }
        $sql = preg_replace('/\s+/', ' ', $stmt->getStatement());
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500).sprintf('...(with %d chars)', strlen($sql));
        }
        /* @phpstan-ignore-next-line */
        $this->logger->$level(self::TAG.$message.sprintf(' %s in %.2fms', $sql, $time), [
            'params' => count($stmt->getBindValues()) > 10
                ? array_slice($stmt->getBindValues(), 0, 10)
                : $stmt->getBindValues(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvent(): string
    {
        return StatementQueriedEvent::class;
    }
}
