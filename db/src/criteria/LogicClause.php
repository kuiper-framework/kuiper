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
    /**
     * @var CriteriaClauseInterface
     */
    private $left;

    /**
     * @var CriteriaClauseInterface
     */
    private $right;

    public function __construct(CriteriaClauseInterface $left, CriteriaClauseInterface $right)
    {
        $this->left = $left;
        $this->right = $right;
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
