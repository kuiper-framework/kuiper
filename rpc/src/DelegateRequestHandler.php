<?php

declare(strict_types=1);

namespace kuiper\rpc;

class DelegateRequestHandler implements RpcRequestHandlerInterface
{
    /**
     * @var callable
     */
    private $delegate;

    /**
     * DelegateRequestHandler constructor.
     *
     * @param callable $delegate
     */
    public function __construct(callable $delegate)
    {
        $this->delegate = $delegate;
    }

    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return ($this->delegate)($request);
    }
}
