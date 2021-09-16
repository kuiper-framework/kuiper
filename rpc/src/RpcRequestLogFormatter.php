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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RpcRequestLogFormatter extends LineRequestLogFormatter
{
    /**
     * {@inheritDoc}
     */
    protected function prepareMessageContext(RequestInterface $request, ?ResponseInterface $response, float $responseTime): array
    {
        $context = parent::prepareMessageContext($request, $response, $responseTime);
        /** @var RpcRequestInterface $request */
        $rpcMethod = $request->getRpcMethod();
        $context['service'] = $rpcMethod->getServiceLocator()->getName();
        $context['method'] = $rpcMethod->getMethodName();
        if ($request instanceof HasRequestIdInterface) {
            $context['request_id'] = $request->getRequestId();
        }
        $context['server_addr'] = $request->getUri()->getHost()
            .($request->getUri()->getPort() > 0 ? ':'.$request->getUri()->getPort() : '');
        if (null !== RpcRequestHelper::getConnectionInfo($request)) {
            $serverRequest = ServerRequestHolder::getRequest();
            if (null !== $serverRequest) {
                $calleeMethod = $serverRequest->getRpcMethod();
                $context['callee_service'] = $calleeMethod->getServiceLocator()->getName();
                $context['callee_method'] = $calleeMethod->getMethodName();
            }
        }
        if (in_array('params', $this->getExtra(), true)) {
            $param = str_replace('"', "'", (string) json_encode($rpcMethod->getArguments(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $context['extra']['params'] = strlen($param) > $this->getBodyMaxSize()
                ? sprintf('%s...%d more', mb_substr($param, 0, $this->getBodyMaxSize()), strlen($param) - $this->getBodyMaxSize())
                : $param;
        }

        return $context;
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
