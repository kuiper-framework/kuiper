<?php

declare(strict_types=1);

namespace kuiper\db\event\listener;

use kuiper\db\event\ShardTableNotExistEvent;
use kuiper\event\EventListenerInterface;

class AutoCreateShardTable implements EventListenerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param ShardTableNotExistEvent $event
     */
    public function __invoke($event): void
    {
        $statement = $event->getStatement();

        $sql = sprintf('CREATE TABLE IF NOT EXISTS `%s` LIKE `%s`', $event->getTable(), $statement->getTable());
        $statement->getPool()->exec($sql);
        $event->setTableCreated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvent(): string
    {
        return ShardTableNotExistEvent::class;
    }
}
