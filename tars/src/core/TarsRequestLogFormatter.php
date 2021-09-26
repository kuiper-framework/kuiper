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

namespace kuiper\tars\core;

use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\tars\client\middleware\AddRequestReferer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TarsRequestLogFormatter extends JsonRpcRequestLogFormatter
{
    /**
     * {@inheritDoc}
     */
    protected function prepareMessageContext(RequestInterface $request, ?ResponseInterface $response, float $startTime, $endTime): array
    {
        $context = parent::prepareMessageContext($request, $response, $startTime, $endTime);
        if ($response instanceof TarsResponseInterface) {
            $packet = $response->getResponsePacket();
            $context['status'] = $packet->iRet;
        }
        /** @var TarsRequestInterface $request */
        $context['referer'] = AddRequestReferer::getReferer($request);

        return $context;
    }
}
