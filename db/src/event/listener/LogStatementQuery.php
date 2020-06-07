<?php

declare(strict_types=1);

namespace kuiper\db\event\listener;

use kuiper\db\event\StatementQueriedEvent;
use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class StatementQueriedEventListener.
 *
 * @EventListener()
 */
class LogStatementQuery implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @param StatementQueriedEvent $event
     */
    public function __invoke($event): void
    {
        $stmt = $event->getStatement();
        $time = 1000 * (microtime(true) - $stmt->getConnection()->getLastQueryStart());
        $level = ($time > 1000) ? 'warning' : 'debug';
        $sql = preg_replace('/\s+/', ' ', $stmt->getStatement());
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500).sprintf('...(with %d chars)', strlen($sql));
        }
        $this->logger->$level(sprintf('[Db] query %s in %.2fms', $sql, $time), [
            'params' => $stmt->getBindValues(),
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
