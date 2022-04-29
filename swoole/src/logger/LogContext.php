<?php

namespace kuiper\swoole\logger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LogContext
{
    public ?RequestInterface $request = null;
    public ?ResponseInterface $response = null;
    public ?Throwable $error = null;
    public float $startTime = 0;
    public float $endTime = 0;
}