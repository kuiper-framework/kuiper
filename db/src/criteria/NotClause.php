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

class NotClause implements CriteriaClauseInterface
{
    public function __construct(private readonly CriteriaClauseInterface $clause)
    {
    }

    public function getClause(): CriteriaClauseInterface
    {
        return $this->clause;
    }
}
