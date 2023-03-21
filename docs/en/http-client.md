# Http Client

kuiper provides HTTP client factory classes and provides a declarative proxy interface similar to [Spring Cloud OpenFeign](https://spring.io/projects/spring-cloud-openfeign). 

## Installation

```bash
composer require kuiper/http-client:^0.8
```

## HttpClientFactory

Configure in 'src/config.php':

```php
[
    'application' => [
        'http_client' => [
            'default' => [
                'logging' => true,
            ]
        ]
    ]
]
```

`default` configures the http client obtained by the container ID `GuzzleHttp\ClientInterface`. Configuration item reference [Guzzle request parameters](https://docs.guzzlephp.org/en/stable/request-options.html) .

Some special parameters are as follows:
- logging whether to enable logging middleware
- log_format Set log format, optional value clf, debug, short or custom format, see `GuzzleHttp\MessageFormatter`
- log_level log level, default info
- The number of retry retries
- handler custom `GuzzleHttp\HandlerStack` object
- middleware Middleware array, which can be a string (get the middleware definition via the container)

All other key values in `application.http_client` are registered with the container and the corresponding http client object is created using HttpClientFactory.
The parameters in `application.http_client.default` will be included.

## HTTP proxy object

The Http proxy object is declared through the PHP interface and is identified with the `#[kuiper\http\client\attribute\HttpClient]` annotation. For example

```php
<?php

use kuiper\http\client\attribute\GetMapping;
use kuiper\http\client\attribute\HttpClient;
use kuiper\http\client\attribute\HttpHeader;

#[HttpClient]
#[HttpHeader("content-type", "application/json")]
interface GithubService
{
    /**
     * @return GitRepository[]
     */
    #[GetMapping("/users/{user}/list")]
    public function listRepos(string $user): array;
}
```

http request methods and paths are declared with annotations such as `kuiper\http\client\attribute\GetMapping`.
Placeholders such as `{user}` in the path are replaced with parameter values of the same name in the method parameters. All other parameters in the method are converted to Guzzle's request parameters.
If the request method is `GET`, as a query parameter; Other request methods, depending on the content-type setting, if `application/json`
as a json parameter; If `multipart/form-data`, as a multipart parameter.
If the parameter is an object type, it is converted to an array using `kuiper\serializer\NormalizerInterface::normalize`.
If the parameter is a `kuiper\http\client\request\File` object or a resource type, `multipart/form-data` is used for file upload.

When the above parameter parsing does not meet the requirements, you can directly set the Guzzle request parameters by using the `kuiper\http\client\request\Request` object.

http headers can be set via the `kuiper\http\client\attribute\HttpHeader` annotation, and value values can be replaced with parameter placeholders with parameters in methods such as:

```php
<?php

interface GithubService
{
    #[GetMapping("/users/{user}/list")]
    #[HttpHeader("Authorization", "Bearer {token}")]
    public function listRepos(string $user, string $token): array;
}
```

http responses are parsed by default using `kuiper\http\client\HttpJsonResponseFactory`, and can only parse json results.
When responding to a response with a status code other than 20X, `GuzzleHttp\ExceptionRequestException` will be thrown.
If the resolution method does not meet the requirements, you can implement the `kuiper\rpc\client\RpcResponseFactoryInterface` interface and configure the class name
The responseParser property to the `HttpClient` annotation. If you need to parse non-20X response results, you need to set `http_errors` to false in the http client options.

The HTTP client configuration item for the HTTP proxy object can be set by using the interface name as the key value in `application.http_client`, for example:

```php
[
    'application' => [
        'http_client' => [
            GithubService::class => [
                'logging' => true,
            ]
        ]
    ]
]
```

## Configuration items

| Configuration Item | Environment variables | Description |
|--------------------------------|------------------------|--------------------------------|
| http_client.default.logging    | HTTP_CLIENT_LOGGING    | Whether to print HTTP request logs |
| http_client.default.log_format | HTTP_CLIENT_LOG_FORMAT | Log format, support clf, short, debug three formats |
| http_client.default.retry      | HTTP_CLIENT_RETRY      | Number of retries, no retries by default |
