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

class NamespaceAsHost implements MiddlewareInterface
{
    public function __construct(
        private readonly ?int $port = null,
        private readonly ?string $subDomain = null
    ) {
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $uri = $request->getUri();
        if ('' === $uri->getHost()) {
            $host = $request->getRpcMethod()->getServiceLocator()->getNamespace();
            $newUri = $uri->withHost($host.$this->subDomain);
            if (isset($this->port)) {
                $newUri = $newUri->withPort($this->port);
            }

            return $handler->handle($request->withUri($newUri));
        }

        return $handler->handle($request);
    }
}
