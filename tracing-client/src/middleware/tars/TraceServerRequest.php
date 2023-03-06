<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware\tars;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tracing\Config;
use kuiper\tracing\Constants;
use kuiper\tracing\middleware\AbstractServerMiddleware;
use Psr\Http\Message\RequestInterface;

class TraceServerRequest extends AbstractServerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
        parent::__construct($this->config, Constants::CODEC_TARS);
    }

    protected function getMethodName(RequestInterface $request): string
    {
        /** @var RpcRequestInterface $request */
        $serviceName = $request->getRpcMethod()->getServiceLocator()->getName();
        $methodName = $request->getRpcMethod()->getMethodName();

        return $serviceName.'.'.$methodName;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $debugIdHeader = $this->config->getDebugIdHeaderKey();
        /** @var TarsRequestInterface|RpcRequestInterface $request */
        $status = $request->getStatus();
        if (!array_key_exists($debugIdHeader, $status)) {
            return $handler->handle($request);
        }

        return $this->handle($request, function (RpcRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        });
    }
}
