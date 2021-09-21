# Cache

Kuiper cache 提供协程环境下的基于 redis 缓存方案。Kuiper cache 基于 PSR-6 和 PSR-16 接口，
本身并没有实现接口，而是需要使用 [Stash](https://www.stashphp.com/) 或 [Symfony Cache](https://symfony.com/doc/current/components/cache.html) .

## 安装 

```bash
composer require kuiper/cache:^0.6
```

在 `src/config.php` 中配置：

```php
use kuiper\helper\env;

return [
    'application' => [
        'cache' => [
            'implementation' => 'stash',
            'namespace' => env('CACHE_PREFIX'),
            'lifetime' => (int) env('CACHE_LIFETIME', 300),
            'memory' => [
                'max_items' => (int) env('CACHE_MEMORY_MAX_ITEMS', 1000)
            ],
        ],
        'redis' => [
            'host' => env('REDIS_HOST', 'localhost'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWARD'),
            'database' => (int) env('REDIS_DATABASE')
        ]
    ]
];
```

配置项说明：

- `application.cache.implementation` 设置缓存实现，可以使用 stash 或者 symfony 
- `application.cache.namespace` 设置缓存命名空间前缀
- `application.cache.lifetime` 使用缓存默认过期事件
- `application.cache.memory` 设置是否使用内存缓存加速
- `application.redis` 设置 redis 连接选项

> kuiper cache 默认使用 stash 缓存实现，原因是 stash Redis 提供按前缀删除缓存的方案，另外，symfony redis
> cache 会把所有 key 存到内存中，在缓存可以较多时消耗内存。

## 使用

基于 PSR-6 或者 PSR-16 接口使用缓存。例如：

```php
$cache = $container->get(\Psr\Cache\CacheItemPoolInterface::class);
$item = $cache->getItem("foo");
if (!$item->isHit()) {
    // fetch data
    $cache->save($item->set($value));
}
$value = $item->get();
```

使用 stash 实现可以基于缓存前缀删除缓存（使用 `/` 作为前缀分隔符）。为和普通缓存 key 区分，基于前缀的缓存 key 必须使用 `group.` 开头。例如：

```php
$cache = $container->get(\Psr\Cache\CacheItemPoolInterface::class);
$item = $cache->getItem("group.foo/bar");
$cache->save($item->set("bar content"));
$cache->deleteItem("group.foo");
echo $cache->hasItem("group.foo/bar");
```

