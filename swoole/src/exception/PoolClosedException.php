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

namespace kuiper\swoole\exception;

use Exception;

class PoolClosedException extends Exception
{
    /**
     * PoolTimeoutException constructor.
     */
    public function __construct()
    {
        parent::__construct('Cannot get connection because pool is closed');
    }
}
