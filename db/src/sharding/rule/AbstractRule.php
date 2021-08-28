<?php

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
