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

namespace kuiper\rpc\client\middleware;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\swoole\Application;

/**
 * 在 header 中添加调用方信息.
 */
class AddRequestReferer implements MiddlewareInterface
{
    public const HEADER_NAME = 'referer';

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        return $handler->handle($request->withHeader(self::HEADER_NAME, Application::getInstance()->getConfig()->get('application.name')));
    }

    public static function getReferer(RpcRequestInterface $request): string
    {
        return $request->getHeaderLine(self::HEADER_NAME);
    }
}
