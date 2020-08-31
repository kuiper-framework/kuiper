<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use kuiper\swoole\pool\PoolFactoryInterface;
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

        return new PooledHttpClient($this->poolFactory, $options);
    }
}
