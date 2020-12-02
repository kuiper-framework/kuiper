<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\Repository;

/**
 * @Repository(entityClass=Item::class)
 */
class ItemRepository extends AbstractCrudRepository
{
}
