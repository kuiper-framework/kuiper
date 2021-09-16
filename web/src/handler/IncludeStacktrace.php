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

namespace kuiper\web\handler;

class IncludeStacktrace
{
    public const NEVER = 'never';
    public const ALWAYS = 'always';
    public const ON_TRACE_PARAM = 'on_trace_param';
}
