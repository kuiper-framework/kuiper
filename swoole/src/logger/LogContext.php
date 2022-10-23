<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\swoole\logger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface LogContext
{
    /**
     * Gets the request.
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface;

    /**
     * Gets the response.
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface;

    /**
     * @return bool
     */
    public function hasResponse(): bool;

    /**
     * Gets the error.
     *
     * @return Throwable|null
     */
    public function getError(): ?Throwable;

    /**
     * @return bool
     */
    public function hasError(): bool;

    /**
     * @return float
     */
    public function getStartTime(): float;

    /**
     * @return float
     */
    public function getEndTime(): float;

    /**
     * @return float
     */
    public function getRequestTime(): float;
}
