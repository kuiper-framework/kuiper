<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

class NotClause implements CriteriaClauseInterface
{
    /**
     * @var CriteriaClauseInterface
     */
    private $clause;

    /**
     * NotClause constructor.
     *
     * @param $clause
     */
    public function __construct(CriteriaClauseInterface $clause)
    {
        $this->clause = $clause;
    }

    public function getClause(): CriteriaClauseInterface
    {
        return $this->clause;
    }
}
