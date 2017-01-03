<?php

namespace kuiper\cache;

use kuiper\cache\driver\Predis;

class PredisDriverTest extends BaseDriverTestCase
{
    protected function createCachePool()
    {
        $redis = new \Predis\Client('tcp://localhost?database=15');
        $redis->flushdb();

        return new Pool(new Predis($redis));
    }
}
