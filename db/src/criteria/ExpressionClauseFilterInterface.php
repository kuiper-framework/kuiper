<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

interface ExpressionClauseFilterInterface
{
    public function filter(ExpressionClause $clause): CriteriaClauseInterface;
}
