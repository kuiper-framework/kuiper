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

class IdentityRule implements RuleInterface
{
    public function __construct(private readonly int|string $id)
    {
    }

    public function getPartition(array $fields): int|string
    {
        return $this->id;
    }
}
