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
    /**
     * @var string
     */
    protected $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Gets the sharding partition by value.
     *
     * @param mixed $value
     *
     * @return int|string
     */
    abstract protected function getPartitionFor($value);

    /**
     * {@inheritDoc}
     */
    public function getPartition(array $fields)
    {
        return $this->getPartitionFor($fields[$this->field] ?? null);
    }
}
