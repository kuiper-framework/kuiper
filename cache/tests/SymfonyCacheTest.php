<?php

declare(strict_types=1);

namespace kuiper\cache;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SymfonyCacheTest extends CacheTestCase
{
    public function testStampedePrevention(): void
    {
        $cache = $this->createContainer([
            'application' => [
                'redis' => [
                    'database' => 10,
                ],
            ],
        ])->get(CacheInterface::class);
        $value = $cache->get('foo', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            error_log('set foo value');
            // $item->tag('tag1');
            return 'bar';
        }, 1.0);
        var_export($value);
        $this->assertEquals('bar', $value);
    }
}
