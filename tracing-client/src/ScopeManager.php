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

namespace kuiper\tracing;

use OpenTracing\Scope as OTScope;
use OpenTracing\ScopeManager as OTScopeManager;
use OpenTracing\Span as OTSpan;

/**
 * {@inheritdoc}
 */
class ScopeManager implements OTScopeManager
{
    /**
     * @var OTScope
     */
    private $active;

    /**
     * {@inheritdoc}
     */
    public function activate(OTSpan $span, bool $finishSpanOnClose = self::DEFAULT_FINISH_SPAN_ON_CLOSE): OTScope
    {
        $this->active = new Scope($this, $span, $finishSpanOnClose);

        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive(): ?OTScope
    {
        return $this->active;
    }

    /**
     * Sets the scope as active.
     *
     * @param OTScope|null $scope
     */
    public function setActive(OTScope $scope = null): void
    {
        $this->active = $scope;
    }
}
