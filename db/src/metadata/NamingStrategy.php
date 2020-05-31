<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\helper\Text;

class NamingStrategy implements NamingStrategyInterface
{
    /**
     * @var string
     */
    private $tablePrefix;

    public function __construct(string $tablePrefix = '')
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function toTableName(NamingContext $context): string
    {
        $tableName = $context->getAnnotationValue() ?: $context->getEntityClassShortName();

        return $this->tablePrefix.Text::snakeCase($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function toColumnName(NamingContext $context): string
    {
        return Text::snakeCase($context->getAnnotationValue() ?: $context->getPropertyName());
    }
}
