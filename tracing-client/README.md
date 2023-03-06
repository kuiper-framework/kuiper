# Bindings for PHP OpenTracing API

This is a client-side library that can be used to instrument PHP apps for distributed trace collection.
See the [OpenTracing PHP API](https://github.com/opentracing/opentracing-php)
for additional detail.

## Installation

Tracing client can be installed via Composer:

```bash
composer require kuiper/tracing-client
```

## Getting Started

Configuration:

```php
<?php
return [
    'application' => [
        'tracing' => [
            'enabled' => env('TRACING_ENABLED') === 'true',
            'debug_id_header_key' => env('TRACING_DEBUG_ID_HEADER', 'tracing-debug-id'),
            'service_name' => env('TRACING_SERVICE_NAME'),
            'tags' => env('TRACING_TAGS'),
            'sampler' => [
                'type' => env('TRACING_SAMPLER_TYPE'),
                'param' => env('TRACING_SAMPLER_PARAM')
            ],
        ],
    ]
]
```

### Samplers

List of supported samplers, for more info about samplers, please read [Jaeger Sampling](https://www.jaegertracing.io/docs/1.9/sampling/) guide.

#### Const sampler
This sampler either samples everything, or nothing.

##### Configuration
```
'sampler' => [
    'type' => Jaeger\SAMPLER_TYPE_CONST,
    'param' => true, // boolean wheter to trace or not
],
```

#### Probabilistic sampler
This sampler samples request by given rate.

##### Configuration
```
'sampler' => [
    'type' => Jaeger\SAMPLER_TYPE_PROBABILISTIC,
    'param' => 0.5, // float [0.0, 1.0]
],
```

#### Rate limiting sampler
Samples maximum specified number of traces (requests) per second.

##### Requirements
* `psr/cache` PSR-6 cache component to store and retrieve sampler state between requests.
Cache component is passed to `Jaeger\Config` trough its constructor.
* `hrtime()` function, that can retrieve time in nanoseconds. You need either `php 7.3` or [PECL/hrtime](http://pecl.php.net/package/hrtime) extension.

##### Configuration
```
'sampler' => [
    'type' => Jaeger\SAMPLER_TYPE_RATE_LIMITING,
    'param' => 100 // integer maximum number of traces per second,
    'cache' => [
        'currentBalanceKey' => 'rate.currentBalance' // string
        'lastTickKey' => 'rate.lastTick' // string
    ]
],
```

## Testing

Tests are located in the `tests` directory. See [tests/README.md](./tests/README.md).

## Roadmap

- [Support Span baggage](https://github.com/jonahgeorge/jaeger-client-php/issues/5)
- [Support Tracer metrics](https://github.com/jonahgeorge/jaeger-client-php/issues/12)
- [Support Tracer error reporting](https://github.com/jonahgeorge/jaeger-client-php/issues/13)

## License

[MIT License](./LICENSE).

[ci-img]: https://travis-ci.org/jonahgeorge/jaeger-client-php.svg?branch=travis
[ci]: https://travis-ci.org/jonahgeorge/jaeger-client-php
[packagist-img]: https://badge.fury.io/ph/jonahgeorge%2Fjaeger-client-php.svg
[packagist]: https://badge.fury.io/ph/jonahgeorge%2Fjaeger-client-php
