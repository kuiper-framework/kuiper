<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

interface ResponseParser
{
    /**
     * @return mixed
     */
    public function parse(MethodMetadata $method, ResponseInterface $response);

    /**
     * @return mixed
     */
    public function handleError(MethodMetadata $method, RequestException $exception);
}
