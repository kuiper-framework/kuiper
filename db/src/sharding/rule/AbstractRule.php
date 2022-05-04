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

namespace kuiper\db\sharding\rule;

abstract class AbstractRule implements RuleInterface
{
    public function __construct(private readonly string $field)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Gets the sharding partition by value.
     */
    abstract protected function getPartitionFor(mixed $value): int|string;

    /**
     * {@inheritDoc}
     */
    public function getPartition(array $fields): int|string
    {
        return $this->getPartitionFor($fields[$this->field] ?? null);
    }
}
