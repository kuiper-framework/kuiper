<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Webmozart\Assert\Assert;

class HashRule extends AbstractRule
{
    /**
     * @var int
     */
    protected $bucket;

    public function __construct($field, $bucket)
    {
        parent::__construct($field);
        $this->bucket = $bucket;
    }

    protected function getPartitionFor($value)
    {
        Assert::integerish($value, "Value of column '{$this->field}' must be an integer, Got %s");

        return $value % $this->bucket;
    }
}
