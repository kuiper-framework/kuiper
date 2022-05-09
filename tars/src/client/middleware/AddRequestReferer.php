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

    public function __construct(private readonly ServerProperties $serverProperties)
    {
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        /** @var TarsRequest $request */
        $request->addContext(self::CONTEXT_KEY, $this->serverProperties->getServerName());

        return $handler->handle($request);
    }

    public static function getReferer(TarsRequestInterface $request): ?string
    {
        return $request->getContext()[self::CONTEXT_KEY] ?? null;
    }
}
