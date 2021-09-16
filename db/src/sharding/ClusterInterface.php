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

namespace kuiper\db\sharding;

use kuiper\db\QueryBuilderInterface;

interface ClusterInterface extends QueryBuilderInterface
{
    /**
     * Gets the table sharding strategy.
     */
    public function getTableStrategy(string $table): StrategyInterface;
}
