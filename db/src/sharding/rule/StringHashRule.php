<?php

declare(strict_types=1);

namespace kuiper\db\sharding\rule;

class StringHashRule extends HashRule
{
    /**
     * @var callable
     */
    protected $hashFunction;

    /**
     * StringHashRule constructor.
     *
     * @param string|callable $hashFunction
     */
    public function __construct(string $field, int $bucket, $hashFunction = 'crc32')
    {
        parent::__construct($field, $bucket);
        $this->hashFunction = $hashFunction;
    }

    protected function getPartitionFor($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Value of column '{$this->field}' must be a string, Got $value");
        }

        return parent::getPartitionFor(call_user_func($this->hashFunction, $value));
    }
}
