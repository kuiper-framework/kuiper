<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

interface CriteriaFilterInterface
{
    public function filter(CriteriaClauseInterface $clause): CriteriaClauseInterface;
}
