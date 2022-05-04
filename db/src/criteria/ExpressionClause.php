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

use kuiper\db\Criteria;
use kuiper\helper\Text;

class ExpressionClause implements CriteriaClauseInterface
{
    public function __construct(
        private readonly string $column,
        private readonly string $operator,
        private readonly mixed $value)
    {
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
    public function getValue(): mixed
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
