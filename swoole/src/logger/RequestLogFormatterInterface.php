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

namespace kuiper\swoole\logger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface RequestLogFormatterInterface
{
    /**
     * @param RequestInterface       $request
     * @param ResponseInterface|null $response
     * @param Throwable|null         $error
     * @param float                  $startTime
     * @param float                  $endTime
     *
     * @return array formatted message and extra info
     */
    public function format(RequestInterface $request, ?ResponseInterface $response, ?Throwable $error, float $startTime, float $endTime): array;
}
