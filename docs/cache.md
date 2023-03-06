# Cache

Kuiper cache 提供协程环境下的基于 redis 缓存方案。Kuiper cache 
基于 [Symfony Cache](https://symfony.com/doc/current/components/cache.html)，提供 PSR-6 和 PSR-16 接口实现.

## 安装 

```bash
composer require kuiper/cache:^0.8
```

配置项说明：

| 配置项                   | 环境变量                  | 说明           |
|-----------------------|-----------------------|--------------|
| cache.namespace       | CACHE_NAMESPACE       | 缓存键前缀        |
| cache.lifetime        | CACHE_LIFETIME        | 缓存默认过期时间     |
| cache.memory.lifetime | CACHE_MEMORY_LIFETIME | 内存缓存过期时间     |
| redis.host            | REDIS_HOST            | redis 服务器地址  |
| redis.port            | REDIS_PORT            | redis 服务器端口号 |
| redis.password        | REDIS_PASSWORD        | redis 服务器密码  |
| redis.database        | REDIS_DATABASE        | redis 数据库序号  |

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

如果需要使用 symfony cache 的特性，可以使用 `Symfony\Contracts\Cache\CacheInterface` 实现：
```php
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

$beta = 1.0;
$cache = $container->get(\Symfony\Contracts\Cache\CacheInterface::class);
$value = $cache->get('my_cache_key', function (ItemInterface $item) {
    $item->expiresAfter(3600);
    $item->tag(['tag_0', 'tag_1']);

    return '...';
}, $beta);
```