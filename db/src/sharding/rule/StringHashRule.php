<?php

declare(strict_types=1);

namespace kuiper\db\sharding\rule;

class StringHashRule extends AbstractRule
{
    /**
     * @var int
     */
    protected $bucket;

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
        parent::__construct($field);
        $this->bucket = $bucket;
        $this->hashFunction = $hashFunction;
    }

    protected function getPartitionFor($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Value of column '{$this->field}' must be a string, Got $value");
        }

        return call_user_func($this->hashFunction, $value) % $this->bucket;
    }
}
