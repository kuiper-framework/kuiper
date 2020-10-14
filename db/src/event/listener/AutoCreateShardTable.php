<?php

declare(strict_types=1);

namespace kuiper\db\event\listener;

use kuiper\db\event\ShardTableNotExistEvent;
use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use Webmozart\Assert\Assert;

/**
 * @EventListener()
 */
class AutoCreateShardTable implements EventListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, ShardTableNotExistEvent::class);
        /** @var ShardTableNotExistEvent $event */
        $statement = $event->getStatement();

        $sql = sprintf('CREATE TABLE IF NOT EXISTS `%s` LIKE `%s`', $event->getTable(), $statement->getTable());
        $statement->getConnection()->exec($sql);
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
