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
