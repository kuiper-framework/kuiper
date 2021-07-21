<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface RequestHandlerInterface
{
    /**
     * @throws \Exception
     */
    public function handle(RequestInterface $request): ResponseInterface;
}
