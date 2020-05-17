<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Http\Message\ServerRequestInterface;

interface SessionFactoryInterface
{
    public function create(ServerRequestInterface $request): SessionInterface;
}
