<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

use kuiper\db\metadata\MetaModelInterface;
use kuiper\db\metadata\MetaModelProperty;

class MetaModelExpressionClauseFilter implements ExpressionClauseFilterInterface
{
    /**
     * @var MetaModelInterface
     */
    private $metaModel;

    public function __construct(MetaModelInterface $metaModel)
    {
        $this->metaModel = $metaModel;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(ExpressionClause $expressionClause): CriteriaClauseInterface
    {
        $property = $this->metaModel->getProperty($expressionClause->getColumn());
        if (!isset($property)) {
            return $expressionClause;
        }

        /** @var MetaModelProperty $property */
        $columns = $property->getColumns();
        if (count($columns) > 1) {
            if ($expressionClause->isInClause()) {
            } elseif ($expressionClause->isEqualClause()) {
            } else {
                throw new \InvalidArgumentException('');
            }
        } else {
            $column = current($columns);
            $value = $expressionClause->getValue();
            if ($expressionClause->isInClause()) {
                $value = array_map(static function ($item) use ($property) {
                    $columnValues = $property->getColumnValues($item);

                    return current($columnValues);
                }, $value);
            } elseif (!$expressionClause->isLikeClause()) {
                $columnValues = $property->getColumnValues($value);
                $value = current($columnValues);
            }

            return new ExpressionClause($column->getName(), $expressionClause->getOperator(), $value);
        }
    }
}
