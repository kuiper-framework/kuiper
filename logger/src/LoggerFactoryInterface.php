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

namespace kuiper\logger;

use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    /**
     * Creates logger for the class.
     */
    public function create(string $className = null): LoggerInterface;
}
