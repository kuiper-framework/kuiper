<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\annotation\Entity;
use kuiper\db\sharding\AbstractShardingCrudRepository;

/**
 * @Entity(Employee::class)
 */
class EmployeeRepository extends AbstractShardingCrudRepository
{
}
