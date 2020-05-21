<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Webmozart\Assert\Assert;

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

    public function __construct($field, $bucket, $hashFunction = 'crc32')
    {
        parent::__construct($field);
        $this->bucket = $bucket;
        $this->hashFunction = $hashFunction;
    }

    protected function getPartitionFor($value)
    {
        Assert::string($value, "Value of column '{$this->field}' must be a string, Got %s");

        return call_user_func($this->hashFunction, $value) % $this->bucket;
    }
}
