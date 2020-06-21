<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;

interface HttpClientFactoryInterface
{
    public function create(array $options = []): ClientInterface;
}
