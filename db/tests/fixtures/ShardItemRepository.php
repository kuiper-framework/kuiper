<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\annotation\Repository;
use kuiper\db\sharding\AbstractShardingCrudRepository;

/**
 * @Repository(entityClass=Item::class)
 */
class ShardItemRepository extends AbstractShardingCrudRepository
{
}
