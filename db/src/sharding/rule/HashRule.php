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

use InvalidArgumentException;

use function is_numeric;

class HashRule extends AbstractRule
{
    public function __construct(string $field, private readonly int $bucket)
    {
        parent::__construct($field);
    }

    public function getBucket(): int
    {
        return $this->bucket;
    }

    protected function getPartitionFor(mixed $value): string|int
    {
        if (!is_numeric($value) || $value != (int) $value) {
            throw new InvalidArgumentException("Value of column '{$this->getField()}' must be an integer, Got $value");
        }

        return $value % $this->bucket;
    }
}
