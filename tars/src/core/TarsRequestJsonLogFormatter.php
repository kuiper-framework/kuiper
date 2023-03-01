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

use kuiper\rpc\RpcRequestJsonLogFormatter;
use kuiper\rpc\RpcServerRequestInterface;
use kuiper\swoole\logger\LogContext;
use kuiper\tars\client\middleware\AddRequestReferer;

class TarsRequestJsonLogFormatter extends RpcRequestJsonLogFormatter
{
    /**
     * {@inheritDoc}
     */
    protected function prepareMessageContext(LogContext $context): array
    {
        $message = parent::prepareMessageContext($context);
        $response = $context->getResponse();
        if ($response instanceof TarsResponseInterface) {
            $packet = $response->getResponsePacket();
            $message['status'] = $packet->iRet;
        }
        /** @var TarsRequestInterface $request */
        $request = $context->getRequest();
        if (!($request instanceof RpcServerRequestInterface)) {
            $message['referer'] = AddRequestReferer::getReferer($request);
        }

        return $message;
    }
}
