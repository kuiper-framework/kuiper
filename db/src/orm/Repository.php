<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\db\ConnectionInterface;
use kuiper\db\orm\serializer\SerializerRegistry;

class Repository extends AbstractRepository
{
    /**
     * Repository constructor.
     */
    public function __construct(ConnectionInterface $connection, TableMetadata $tableMetadata, SerializerRegistry $serializers)
    {
        parent::__construct($connection, $tableMetadata, $serializers);
    }
}
