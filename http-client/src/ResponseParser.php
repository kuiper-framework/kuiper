<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

interface ResponseParser
{
    public function parse(MethodMetadata $method, ResponseInterface $response);

    public function handleError(MethodMetadata $method, RequestException $exception);
}
