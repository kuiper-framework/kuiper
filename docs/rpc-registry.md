## 服务发现

通过使用 consul 等服务注册中心可以自动发现服务。

```bash
composer require kuiper/rpc-registry:^0.8
```

在 `src/config.php` 中添加 consul 服务地址配置：

```php
[
    'application' => [
        'consul' => [
            'base_uri' => "http://consul:8500",
        ]
    ]
]
```
consul 其他配置参数参考 [HttpClient](http-client.md) 。

对于 rpc 服务端，需要添加事件监听器：

```php
[
    'application' => [
        'listeners' => [
            \kuiper\rpc\server\listener\ServiceDiscoveryListener::class
        ],
    ]
]
```
在服务启动时会自动将当前服务地址注册到 consul 中。

对于 rpc 客户端，需要添加中间件：

```php
[
    'application' => [
        'jsonrpc' => [
            'client'=> [
                'middleware' => [
                    \kuiper\rpc\client\middleware\ServiceDiscovery::class,
                ]
            ]
        ]
    ]
]
```

## 配置项

| 配置项                                             | 环境变量                                  | 说明                                       |
|-------------------------------------------------|---------------------------------------|------------------------------------------|
| server.service_discovery.type                   | SERVER_SERVICE_DISCOVERY_TYPE         | 服务端服务注册类型，目前只支持 consul                   |
| server.service_discovery.healthy_check_interval |                                       | 健康检查间隔时间                                 |
| server.service_discovery.healthy_check_path     |                                       | 健康检查地址                                   |
| client.service_discovery.type                   | CLIENT_SERVICE_DISCOVERY_TYPE         | 客户端服务发现类型，目前只支持 consul                   |
| client.service_discovery.load_balance           | CLIENT_SERVICE_DISCOVERY_LOAD_BALANCE | 负载均衡算法，可选值 round_robin, random, equality |    
| consul.base_uri                                 | CONSUL_BASE_URI                       | consul 服务地址                              |

