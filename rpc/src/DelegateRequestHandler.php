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

class DelegateRequestHandler implements RpcRequestHandlerInterface
{
    /**
     * @var callable
     */
    private $delegation;

    /**
     * DelegateRequestHandler constructor.
     *
     * @param callable $delegate
     */
    public function __construct(callable $delegate)
    {
        $this->delegation = $delegate;
    }

    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return ($this->delegation)($request);
    }
}
