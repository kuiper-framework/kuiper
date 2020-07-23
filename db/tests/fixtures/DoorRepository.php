<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\Repository;

/**
 * @Repository(entityClass=Door::class)
 */
class DoorRepository extends AbstractCrudRepository
{
}
