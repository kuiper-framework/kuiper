<?php
namespace kuiper\cache;

use kuiper\cache\Pool;
use kuiper\cache\Item;
use kuiper\cache\driver\Apc;

class ApcDriverTest extends BaseDriverTestCase
{
    public function setUp()
    {
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped("extension apcu is not available");
        }
        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped("apc.enable_cli should set to 'on'");
        }
    }
    
    protected function createCachePool()
    {
        apcu_clear_cache();
        return new Pool(new Apc);
    }
}