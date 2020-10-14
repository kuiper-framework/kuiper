<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

use kuiper\db\Criteria;
use kuiper\helper\Text;

class ExpressionClause implements CriteriaClauseInterface
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var mixed
     */
    private $value;

    /**
     * ExpressionClause constructor.
     *
     * @param mixed $value
     */
    public function __construct(string $column, string $operator, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isInClause(): bool
    {
        return Criteria::OPERATOR_IN === $this->operator
            || Criteria::OPERATOR_NOT_IN === $this->operator;
    }

    public function isLikeClause(): bool
    {
        return Text::endsWith(strtolower($this->operator), 'like');
    }

    public function isEqualClause(): bool
    {
        return Criteria::OPERATOR_EQUAL === $this->operator;
    }
}
