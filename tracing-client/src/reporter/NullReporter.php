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
 * NullReporter ignores all spans.
 */
class NullReporter implements ReporterInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close(): void
    {
        // nothing to do
    }
}
