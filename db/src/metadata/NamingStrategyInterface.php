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

interface NamingStrategyInterface
{
    /**
     * Converts table name to physical table name.
     */
    public function toTableName(NamingContext $context): string;

    /**
     * Converts column name to physical column name.
     */
    public function toColumnName(NamingContext $context): string;
}
