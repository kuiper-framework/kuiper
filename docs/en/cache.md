# Cache

Kuiper cache provides a redis-based cache with coroutine. Kuiper cache 
Based on [Symfony Cache](https://symfony.com/doc/current/components/cache.html).
PSR-6 and PSR-16 interface implementations are provided.

## Installation 

```bash
composer require kuiper/cache:^0.8
```

Cache Configurations:

| Configuration Item | Environment variables | Description |
|-----------------------|-----------------------|--------------|
| cache.namespace       | CACHE_NAMESPACE       | Cache key prefix |
| cache.lifetime        | CACHE_LIFETIME        | Cache default expiration |
| cache.memory.lifetime | CACHE_MEMORY_LIFETIME | Memory cache expiration |
| redis.host            | REDIS_HOST            | Redis server address |
| redis.port            | REDIS_PORT            | Redis server port number |
| redis.password        | REDIS_PASSWORD        | Redis server password |
| redis.database        | REDIS_DATABASE        | Redis database ordinal |

## Usage

Use caching with PSR-6 or PSR-16 interfaces. For example:

```php
$cache = $container->get(\Psr\Cache\CacheItemPoolInterface::class);
$item = $cache->getItem("foo");
if (!$item->isHit()) {
    // fetch data
    $cache->save($item->set($value));
}
$value = $item->get();
```

If you need to use the features of the symfony cache, you can use `Symfony\Contracts\Cache\CacheInterface`:

```php
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

$beta = 1.0;
$cache = $container->get(CacheInterface::class);
$value = $cache->get('my_cache_key', function (ItemInterface $item) {
    $item->expiresAfter(3600);
    $item->tag(['tag_0', 'tag_1']);

return '...';
}, $beta);
```
