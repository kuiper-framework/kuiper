<?php


namespace kuiper\cache;


use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use function kuiper\helper\env;

class SymfonyCacheTest extends CacheTestCase
{
    public function testStampedePrevention(): void
    {
        $cache = $this->createContainer([
            'application' => [
                'cache' => [
                    'namespace' => 'test',
                    'lifetime' => 30,
                ],
                'redis' => [
                    'host' => env('REDIS_HOST'),
                ],
            ],
        ])->get(CacheInterface::class);
        $value = $cache->get('foo2', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            error_log("set foo value");
            //$item->tag('tag1');
            return 'bar';
        }, 1.0);
        // var_export($value);
        $this->assertEquals('bar', $value);
    }

}