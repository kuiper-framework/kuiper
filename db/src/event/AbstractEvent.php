<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\ConnectionInterface;
use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
