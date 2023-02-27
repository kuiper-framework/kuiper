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

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HttpClientFactory implements HttpClientFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly PoolFactoryInterface $poolFactory)
    {
    }

    public function create(array $options = []): ClientInterface
    {
        if (!isset($options['handler'])) {
            $options['handler'] = HandlerStack::create();
        }
        if (!empty($options['logging'])) {
            $format = strtoupper($options['log_format'] ?? 'clf');
            if (defined(MessageFormatter::class.'::'.$format)) {
                $format = constant(MessageFormatter::class.'::'.$format);
            }
            $formatter = new MessageFormatter($format);
            $middleware = Middleware::log($this->logger, $formatter, strtolower($options['log_level'] ?? 'info'));
            $options['handler']->push($middleware);
        }
        if (!empty($options['retry'])) {
            if (is_callable($options['retry'])) {
                $options['handler']->push($options['retry']);
            } else {
                $options['handler']->push(Middleware::retry($this->createRetryCallback((int) $options['retry'])));
            }
        }
        if (!empty($options['middleware'])) {
            foreach ($options['middleware'] as $middleware) {
                $options['handler']->push($middleware);
            }
        }

        /** @var ClientInterface $client */
        $client = ConnectionProxyGenerator::create($this->poolFactory, ClientInterface::class, static function () use ($options) {
            return new Client($options);
        });

        return $client;
    }

    public function createRetryCallback(int $maxRetries): callable
    {
        return static function ($retries, RequestInterface $req, ResponseInterface $resp = null, Exception $e = null) use ($maxRetries): bool {
            if ($retries >= $maxRetries) {
                return false;
            }
            if ($e instanceof ConnectException) {
                return true;
            }
            if (null !== $resp && $resp->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }
}
