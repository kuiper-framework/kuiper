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

class HashRule extends AbstractRule
{
    /**
     * @var int
     */
    protected $bucket;

    public function __construct(string $field, int $bucket)
    {
        parent::__construct($field);
        $this->bucket = $bucket;
    }

    public function getBucket(): int
    {
        return $this->bucket;
    }

    protected function getPartitionFor($value)
    {
        if (!\is_numeric($value) || $value != (int) $value) {
            throw new \InvalidArgumentException("Value of column '{$this->field}' must be an integer, Got $value");
        }

        return $value % $this->bucket;
    }
}
