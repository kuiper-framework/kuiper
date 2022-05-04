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

namespace kuiper\db\metadata;

use kuiper\helper\Text;

class NamingStrategy implements NamingStrategyInterface
{
    public function __construct(private readonly string $tablePrefix = '')
    {
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
