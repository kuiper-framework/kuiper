<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\Entity;
use kuiper\di\annotation\Repository;

/**
 * @Entity(Department::class)
 * @Repository()
 */
class DepartmentRepository extends AbstractCrudRepository
{
}
