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

namespace kuiper\db\criteria;

class RawClause implements CriteriaClauseInterface
{
    public function __construct(private readonly string $expression,
                                private readonly array $bindValues)
    {
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getBindValues(): array
    {
        return $this->bindValues;
    }
}
