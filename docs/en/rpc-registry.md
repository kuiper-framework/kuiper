## Service discovery

Services can be discovered automatically by using a service registry such as Consul.

```bash
composer require kuiper/rpc-registry:^0.8
```

Add the consul service address configuration in 'src/config.php':

```php
[
    'application' => [
        'consul' => [
            'base_uri' => "http://consul:8500",
        ]
    ]
]
```
For additional configuration parameters of consul, refer to [HttpClient](http-client.md).

For RPC servers, you need to add event listeners:

```php
[
    'application' => [
        'listeners' => [
            \kuiper\rpc\server\listener\ServiceDiscoveryListener::class
        ],
    ]
]
```
The current service address is automatically registered with consul when the service starts.

For RPC clients, you need to add middleware:

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

## Configuration items

| Configuration Item | Environment variables | Description |
|-------------------------------------------------|---------------------------------------|------------------------------------------|
| server.service_discovery.type                   | SERVER_SERVICE_DISCOVERY_TYPE         | The server-side service registration type, currently only supports consul |
| server.service_discovery.healthy_check_interval |                                       | Health check interval |
| server.service_discovery.healthy_check_path     |                                       | Health check address |
| client.service_discovery.type                   | CLIENT_SERVICE_DISCOVERY_TYPE         | Client service discovery type, currently only consul | is supported
| client.service_discovery.load_balance           | CLIENT_SERVICE_DISCOVERY_LOAD_BALANCE | Load balancing algorithm, optional values round_robin, random, equality |    
| consul.base_uri                                 | CONSUL_BASE_URI                       | Consul Service Address |

Next: [Tars Server](tars.md)
