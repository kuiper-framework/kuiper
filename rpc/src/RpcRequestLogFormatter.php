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

namespace kuiper\rpc;

use kuiper\rpc\server\ServerRequestHolder;
use kuiper\swoole\logger\LineRequestLogFormatter;
use kuiper\swoole\logger\LogContext;
use Psr\Http\Message\RequestInterface;

class RpcRequestLogFormatter extends LineRequestLogFormatter
{
    /**
     * {@inheritDoc}
     */
    protected function prepareMessageContext(LogContext $context): array
    {
        $message = parent::prepareMessageContext($context);
        /** @var RpcRequestInterface $request */
        $request = $context->getRequest();
        $rpcMethod = $request->getRpcMethod();
        $message['service'] = $rpcMethod->getServiceLocator()->getName();
        $message['method'] = $rpcMethod->getMethodName();
        if ($request instanceof HasRequestIdInterface) {
            $message['request_id'] = $request->getRequestId();
        }
        $message['server_addr'] = $request->getUri()->getHost()
            .($request->getUri()->getPort() > 0 ? ':'.$request->getUri()->getPort() : '');
        if (null !== RpcRequestHelper::getConnectionInfo($request)) {
            $serverRequest = ServerRequestHolder::getRequest();
            if (null !== $serverRequest) {
                $calleeMethod = $serverRequest->getRpcMethod();
                $message['callee_service'] = $calleeMethod->getServiceLocator()->getName();
                $message['callee_method'] = $calleeMethod->getMethodName();
            }
        } else {
            // client request exchange body bytes
            [$message['body_bytes_recv'], $message['body_bytes_sent']]
            = [$message['body_bytes_sent'], $message['body_bytes_recv']];
        }
        if (in_array('params', $this->getExtra(), true)) {
            $param = str_replace('"', "'", (string) json_encode($rpcMethod->getArguments(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $message['extra']['params'] = strlen($param) > $this->getBodyMaxSize()
                ? sprintf('%s...%d more', mb_substr($param, 0, $this->getBodyMaxSize()), strlen($param) - $this->getBodyMaxSize())
                : $param;
        }

        return $message;
    }

    protected function getIpList(RequestInterface $request): array
    {
        /** @var RpcRequestInterface $request */
        $connInfo = RpcRequestHelper::getConnectionInfo($request);
        if (null !== $connInfo) {
            return [$connInfo->getRemoteIp().':'.$connInfo->getRemotePort()];
        }

        return [];
    }
}
