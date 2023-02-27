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

use InvalidArgumentException;
use kuiper\db\Criteria;
use kuiper\db\metadata\Column;
use kuiper\db\metadata\MetaModelInterface;
use kuiper\db\metadata\MetaModelProperty;

class MetaModelCriteriaFilter implements CriteriaFilterInterface
{
    public function __construct(private readonly MetaModelInterface $metaModel)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function filter(CriteriaClauseInterface $clause): CriteriaClauseInterface
    {
        if ($clause instanceof ExpressionClause) {
            return $this->filterExpressClause($clause);
        }

        return $clause;
    }

    private function filterExpressClause(ExpressionClause $clause): CriteriaClauseInterface
    {
        $property = $this->metaModel->getProperty($clause->getColumn());
        if (!isset($property)) {
            return $clause;
        }

        /** @var MetaModelProperty $property */
        $columns = $property->getColumns();
        if (count($columns) > 1) {
            if ($clause->isEqualClause()) {
                return Criteria::create($property->getColumnValues($clause->getValue()))
                    ->getClause();
            }
            if ($clause->isInClause()) {
                $fields = array_map(static function (Column $column): string {
                    return $column->getName();
                }, $columns);
                $values = array_map(static function ($item) use ($property): array {
                    return $property->getColumnValues($item);
                }, $clause->getValue());

                return Criteria::create()
                    ->matches($values, $fields)
                    ->getClause();
            }
            throw new InvalidArgumentException('only = or in can apply to '.$property->getEntityClass()->getName().'.'.$property->getPath());
        }

        $column = current($columns);
        $value = $clause->getValue();
        if ($clause->isInClause()) {
            $value = array_map(static function ($item) use ($property) {
                $columnValues = $property->getColumnValues($item);

                return current($columnValues);
            }, $value);
        } elseif (!$clause->isLikeClause()) {
            $columnValues = $property->getColumnValues($value);
            $value = current($columnValues);
        }

        return new ExpressionClause($column->getName(), $clause->getOperator(), $value);
    }
}
