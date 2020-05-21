<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

abstract class AbstractRule implements RuleInterface
{
    /**
     * @var string
     */
    protected $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    abstract protected function getPartitionFor($value);

    public function getPartition(array $fields)
    {
        return $this->getPartitionFor(isset($fields[$this->field]) ? $fields[$this->field] : null);
    }
}
