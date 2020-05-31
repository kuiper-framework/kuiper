<?php

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

    /**
     * OrClause constructor.
     *
     * @param $left
     * @param $right
     */
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
