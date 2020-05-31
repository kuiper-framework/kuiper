<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

class RawClause implements CriteriaClauseInterface
{
    /**
     * @var string
     */
    private $expression;

    /**
     * @var array
     */
    private $bindValues;

    public function __construct(string $expression, array $bindValues)
    {
        $this->expression = $expression;
        $this->bindValues = $bindValues;
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
