<?php

declare(strict_types=1);

namespace kuiper\web\handler;

class IncludeStacktrace
{
    public const NEVER = 'never';
    public const ALWAYS = 'always';
    public const ON_TRACE_PARAM = 'on_trace_param';
}
