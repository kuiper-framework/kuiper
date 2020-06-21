<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;
use kuiper\swoole\pool\PoolFactoryInterface;

class HttpClientFactory implements HttpClientFactoryInterface
{
    /**
     * @var PoolFactoryInterface
     */
    private $poolFactory;

    /**
     * HttpClientFactory constructor.
     */
    public function __construct(PoolFactoryInterface $poolFactory)
    {
        $this->poolFactory = $poolFactory;
    }

    public function create(array $options = []): ClientInterface
    {
        return new PooledHttpClient($this->poolFactory, $options);
    }
}
