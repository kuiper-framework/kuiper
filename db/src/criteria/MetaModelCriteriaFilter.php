<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

use kuiper\db\Criteria;
use kuiper\db\metadata\Column;
use kuiper\db\metadata\MetaModelInterface;
use kuiper\db\metadata\MetaModelProperty;

class MetaModelCriteriaFilter implements CriteriaFilterInterface
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
                $fields = array_map(static function (Column $column) {
                    return $column->getName();
                }, $columns);
                $values = array_map(static function ($item) use ($property) {
                    return $property->getColumnValues($item);
                }, $clause->getValue());

                return Criteria::create()
                    ->matches($values, $fields)
                    ->getClause();
            }
            throw new \InvalidArgumentException('only = or in can apply to '.$property->getEntityClass()->getName().'.'.$property->getPath());
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
