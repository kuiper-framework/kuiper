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

class EqualToRule implements RuleInterface
{
    public function __construct(private readonly string $field)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getPartition(array $fields): int|string
    {
        return $fields[$this->field];
    }
}
