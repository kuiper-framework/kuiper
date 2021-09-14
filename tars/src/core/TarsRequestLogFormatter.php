<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\tars\client\middleware\AddRequestReferer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TarsRequestLogFormatter extends JsonRpcRequestLogFormatter
{
    protected function prepareMessageContext(RequestInterface $request, ?ResponseInterface $response, float $responseTime): array
    {
        $context = parent::prepareMessageContext($request, $response, $responseTime);
        if ($response instanceof TarsResponseInterface) {
            $packet = $response->getResponsePacket();
            $context['status'] = $packet->iRet;
        }
        /** @var TarsRequestInterface $request */
        $context['referer'] = AddRequestReferer::getReferer($request);

        return $context;
    }
}
