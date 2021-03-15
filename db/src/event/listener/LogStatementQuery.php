<?php

declare(strict_types=1);

namespace kuiper\db\event\listener;

use kuiper\db\event\StatementQueriedEvent;
use kuiper\db\Statement;
use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

/**
 * Class StatementQueriedEventListener.
 *
 * @EventListener()
 */
class LogStatementQuery implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, StatementQueriedEvent::class);
        /** @var StatementQueriedEvent $event */
        /** @var Statement $stmt */
        $e = $event->getException();
        $stmt = $event->getStatement();
        $time = 1000 * (microtime(true) - $stmt->getConnection()->getLastQueryStart());
        if (null === $e) {
            $level = ($time > 1000) ? 'warning' : 'debug';
        } else {
            $level = 'error';
        }
        $sql = preg_replace('/\s+/', ' ', $stmt->getStatement());
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500).sprintf('...(with %d chars)', strlen($sql));
        }
        $this->logger->$level(sprintf(self::TAG.'query %s in %.2fms', $sql, $time), [
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
