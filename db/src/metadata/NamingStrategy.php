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
        return $this->tablePrefix.
            ($context->getAnnotationValue() ?? Text::snakeCase($context->getEntityClassShortName()));
    }

    /**
     * {@inheritdoc}
     */
    public function toColumnName(NamingContext $context): string
    {
        return $context->getAnnotationValue() ?? Text::snakeCase($context->getPropertyName());
    }
}
