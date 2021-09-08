# Http Client

kuiper 提供 Http 客户端工厂类，并提供类似 [Spring Cloud OpenFeign](https://spring.io/projects/spring-cloud-openfeign) 声明式 
构建 http 代理类。

## HttpClientFactory

首先安装 `guzzlehttp/guzzle` 包：
```bash
composer require guzzlehttp/guzzle
```

在 `src/config.php` 配置：

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

`default` 配置的是容器ID `\GuzzleHttp\ClientInterface` 获取的 http client。配置项参考 [Guzzle 请求参数](https://docs.guzzlephp.org/en/stable/request-options.html) .
一些特殊参数如下：
- logging 是否开启日志中间件
- log_format 设置日志格式，可选值 clf, debug, short 或自定义格式，参考 `\GuzzleHttp\MessageFormatter`
- log_level 日志等级，默认 info
- retry 重试次数
- handler 自定义 `\GuzzleHttp\HandlerStack` 对象
- middleware 中间件数组，可以是字符串（通过容器获取中间件定义）

`application.http_client` 中的其他 key 值都将注册到容器中，并使用 HttpClientFactory 创建对应 http 客户端对象。
创建参数会集成 `application.http_client.default` 中的参数。

## Http 代理对象

Http 代理对象通过 php 接口声明，并使用 `@\kuiper\http\client\annotation\HttpClient` 注解标识。例如

```php
<?php

use kuiper\http\client\annotation\GetMapping;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\RequestHeader;

/**
 * @HttpClient
 * @RequestHeader("content-type: application/json")
 */
interface GithubService
{
    /**
     * @GetMapping("/users/{user}/list")
     *
     * @return GitRepository[]
     */
    public function listRepos(string $user): array;
}
```

http 请求方法和路径通过 `@kuiper\http\client\annotation\GetMapping` 这样注解进行声明，
路径中 `{user}` 这样的占位符会使用方法参数中同名的参数值替换。方法中的其他参数都将转换为 Guzzle 的请求参数。
如果请求方法为 `GET`，则作为 query 参数；其他请求方法，根据 content-type 设置，如果是 `application/json`
则作为 json 参数；如果是 `multipart/form-data`，则作为 multipart 参数。
参数如果是一个对象类型，会使用 `\kuiper\serializer\NormalizerInterface::normalize` 转换为数组。
如果参数是 `\kuiper\http\client\request\File` 对象或者是 resource 类型，将使用 `multipart/form-data` 设置为文件上传。

当上述参数解析不满足需求，可以通过使用 `\kuiper\http\client\request\Request` 对象，直接设置 Guzzle 请求参数。

http 头可以通过 `@\kuiper\http\client\annotation\RequestHeader` 注解设置，value 值可以使用参数占位符，替换为方法中的参数例如：

```php
<?php

interface GithubService
{
    /**
     * @GetMapping("/users/{user}/list")
     * @RequestHeander("Authorization: Bearer {token}")
     * @return GitRepository[]
     */
    public function listRepos(string $user, string $token): array;
}
```

http 响应默认使用 `\kuiper\http\client\HttpJsonResponseFactory` 解析， 只能解析 json 结果。
当响应 status code 非 20X 的响应时， 将抛出 `\GuzzleHttp\Exception\RequestException`。
如果解析方式不满足要求，可以实现 `\kuiper\rpc\client\RpcResponseFactoryInterface` 接口，配置类名
到 `@HttpClient` 注解的 responseParser 属性。如果需要解析非 20X 响应结果，需要在 http 客户端配置
中设置 `http-errors` 为 false。

http 代理对象的 http 客户端配置项可以通过在 `application.http_client` 中使用接口名做为 key 值设置，例如：

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

