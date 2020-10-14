<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HttpClientFactory implements HttpClientFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        if (!isset($options['handler'])) {
            $options['handler'] = HandlerStack::create();
        }
        if (!empty($options['logging'])) {
            $format = strtoupper($options['log-format'] ?? 'clf');
            if (defined(MessageFormatter::class.'::'.$format)) {
                $format = constant(MessageFormatter::class.'::'.$format);
            }
            $formatter = new MessageFormatter($format);
            $middleware = Middleware::log($this->logger, $formatter, strtolower($options['log-level'] ?? 'info'));
            $options['handler']->push($middleware);
        }
        if (!empty($options['retry'])) {
            if (is_callable($options['retry'])) {
                $options['handler']->push($options['retry']);
            } else {
                $options['handler']->push(Middleware::retry($this->createRetryCallback((int) $options['retry'])));
            }
        }

        return new PooledHttpClient($this->poolFactory, $options);
    }

    public function createRetryCallback(int $maxRetries): callable
    {
        return static function ($retries, RequestInterface $req, ResponseInterface $resp = null, \Exception $e = null) use ($maxRetries): bool {
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
