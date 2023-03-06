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

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;

/**
 * Uses to report finished span to something that collects those spans.
 */
interface ReporterInterface
{
    /**
     * Report finished span.
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void;

    /**
     * Release any resources used by the reporter and flushes/sends the data.
     *
     * @return void
     */
    public function close(): void;
}
