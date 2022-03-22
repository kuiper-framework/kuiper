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

namespace kuiper\http\client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PooledHttpClient implements ClientInterface
{
    /**
     * @var PoolInterface
     */
    private $httpClientPool;

    public function __construct(PoolFactoryInterface $poolFactory, array $options = [])
    {
        $this->httpClientPool = $poolFactory->create($options['pool'] ?? 'http-client',
            static function () use ($options): Client {
                return new Client($options);
            });
    }

    /**
     * @return mixed
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    public function __call(string $method, array $args)
    {
        /* @phpstan-ignore-next-line */
        $ret = $this->httpClientPool->take()->$method(...$args);
        $this->httpClientPool->release();

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->__call(__METHOD__, [$request, $options]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->__call(__METHOD__, [$request, $options]);
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        return $this->__call(__METHOD__, [$method, $uri, $options]);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return $this->__call(__METHOD__, [$method, $uri, $options]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(?string $option = null)
    {
        return $this->__call(__METHOD__, [$option]);
    }
}
