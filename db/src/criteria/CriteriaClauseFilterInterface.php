<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

interface CriteriaClauseFilterInterface
{
    public function filter(CriteriaClauseInterface $clause): CriteriaClauseInterface;
}
