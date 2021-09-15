<?php

declare(strict_types=1);

namespace kuiper\tars\client\middleware;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\client\TarsRequest;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\server\ServerProperties;

/**
 * 在 context 中添加调用方信息.
 */
class AddRequestReferer implements MiddlewareInterface
{
    public const CONTEXT_KEY = 'referer';
    /**
     * @var ServerProperties
     */
    private $serverProperties;

    public function __construct(ServerProperties $serverProperties)
    {
        $this->serverProperties = $serverProperties;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        /** @var TarsRequest $request */
        $request->setContext(array_merge($request->getContext(), [
            self::CONTEXT_KEY => $this->serverProperties->getServerName(),
        ]));

        return $handler->handle($request);
    }

    public static function getReferer(TarsRequestInterface $request): ?string
    {
        return $request->getContext()[self::CONTEXT_KEY] ?? null;
    }
}
