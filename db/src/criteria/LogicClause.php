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

abstract class LogicClause implements CriteriaClauseInterface
{
    public function __construct(private readonly CriteriaClauseInterface $left,
                                private readonly CriteriaClauseInterface $right)
    {
    }

    public function getLeft(): CriteriaClauseInterface
    {
        return $this->left;
    }

    public function getRight(): CriteriaClauseInterface
    {
        return $this->right;
    }
}
