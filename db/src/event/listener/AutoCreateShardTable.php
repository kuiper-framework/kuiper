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

        $sql = sprintf('CREATE TABLE IF NOT EXISTS `%s` LIKE `%s`',
            $event->getTable(), $statement->getBaseTable());
        $statement->withConnection(function ($conn) use ($sql) {
            $conn->exec($sql);
        });
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
