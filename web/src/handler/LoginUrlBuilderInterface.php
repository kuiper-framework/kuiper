<?php

declare(strict_types=1);

namespace kuiper\web\handler;

use Psr\Http\Message\ServerRequestInterface;

interface LoginUrlBuilderInterface
{
    public function build(ServerRequestInterface $request): string;
}
