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

namespace kuiper\tracing\middleware\tars;

use kuiper\tracing\Constants;

class TraceClientRequest extends \kuiper\tracing\middleware\rpc\TraceClientRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->format = Constants::CODEC_TARS;
    }
}
