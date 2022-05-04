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

class StringHashRule extends HashRule
{
    /**
     * @var callable
     */
    protected $hashFunction;

    public function __construct(string $field, int $bucket, string|callable $hashFunction = 'crc32')
    {
        parent::__construct($field, $bucket);
        $this->hashFunction = $hashFunction;
    }

    protected function getPartitionFor(mixed $value): int|string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Value of column '{$this->getField()}' must be a string, Got $value");
        }

        return parent::getPartitionFor(call_user_func($this->hashFunction, $value));
    }
}
