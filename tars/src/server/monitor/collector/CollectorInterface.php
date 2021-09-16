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

namespace kuiper\tars\server\monitor\collector;

use kuiper\tars\server\monitor\MetricPolicy;

interface CollectorInterface
{
    /**
     * @see MetricPolicy
     *
     * @return string
     */
    public function getPolicy(): string;

    /**
     * @return array
     */
    public function getValues(): array;
}
