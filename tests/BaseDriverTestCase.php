<?php
namespace kuiper\cache;

use kuiper\test\TestCase;
use kuiper\cache\Item;
use kuiper\test\DotenvLoader;

abstract class BaseDriverTestCase extends TestCase
{
    protected $data = ['test'];
    protected $multiData = [
        'key' => 'value',
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3'
    ];

    public static function setUpBeforeClass()
    {
        DotenvLoader::load(__DIR__);
    }

    abstract protected function createCachePool();

    public function testGetItem()
    {
        $pool = $this->createCachePool();

        $item = $pool->getItem('base/one');
        $this->assertInstanceOf(Item::class, $item);

        $item->set($this->data)->save();
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData);

        $key = $item->getKey();
        $this->assertEquals('base/one', $key, 'Pool sets proper Item key.');
    }

    public function testSaveItem()
    {
        $pool = $this->createCachePool();

        $this->assertFalse($pool->hasItem('base/one'));
        $item = $pool->getItem('base/one');
        $this->assertInstanceOf(Item::class, $item);

        $key = $item->getKey();
        $this->assertEquals('base/one', $key);

        $item->set($this->data);
        $this->assertTrue($pool->save($item));
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData);

        $item = $pool->getItem('base/one');
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData);

        $this->assertTrue($pool->hasItem('base/one'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetItemInvalidKeyMissingNode()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('This/Test//Fail');
    }

    public function testGetItems()
    {
        $pool = $this->createCachePool();
        $keys = array_keys($this->multiData);

        $cacheIterator = $pool->getItems($keys);
        $keyData = $this->multiData;
        foreach ($cacheIterator as $key => $item) {
            $this->assertFalse($item->isHit(), 'new Cache in iterator is empty');
            $item->set($keyData[$key])->save();
            unset($keyData[$key]);
        }
        $this->assertCount(0, $keyData, 'all keys are accounted for the in cache iterator');

        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $key => $item) {
            $this->assertEquals($key, $item->getKey(), 'Item key is not equals key in iterator');
            $data = $item->get($key);
            $this->assertEquals($this->multiData[$key], $data, 'data put into the pool comes back the same through iterators.');
        }
    }

    public function testDeleteItems()
    {
        $pool = $this->createCachePool();

        $keys = array_keys($this->multiData);

        $cacheIterator = $pool->getItems($keys);
        $keyData = $this->multiData;
        foreach ($cacheIterator as $item) {
            $key = $item->getKey();
            $this->assertFalse($item->isHit(), 'new Cache in iterator is empty');
            $item->set($keyData[$key])->save();
            unset($keyData[$key]);
        }
        $this->assertCount(0, $keyData, 'all keys are accounted for the in cache iterator');

        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $item) {
            $key = $item->getKey();
            $data = $item->get($key);
            $this->assertEquals($this->multiData[$key], $data, 'data put into the pool comes back the same through iterators.');
        }

        $this->assertTrue($pool->deleteItems($keys), 'deleteItems returns true.');
        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $item) {
            $this->assertFalse($item->isHit(), 'data cleared using deleteItems is removed from the cache.');
        }
    }

    public function testClearOneLevel()
    {
        $pool = $this->createCachePool();
        $keys = ['base', 'base/one'];
        foreach ($keys as $key) {
            $item = $pool->getItem($key);
            $pool->save($item->set($this->data));
        }

        $this->assertTrue($pool->hasItem($keys[0]) && $pool->hasItem($keys[1]));

        //clear
        $pool->deleteItem($keys[0].'/');
        $this->assertFalse($pool->hasItem($keys[0]));
        $this->assertFalse($pool->hasItem($keys[1]));
    }

    public function testClearTwoLevel()
    {
        $pool = $this->createCachePool();
        $keys = ['base', 'base/one', 'base/one/two'];
        foreach ($keys as $key) {
            $item = $pool->getItem($key);
            $pool->save($item->set($this->data));
        }

        $this->assertTrue($pool->hasItem($keys[0]) && $pool->hasItem($keys[1]) && $pool->hasItem($keys[2]));
 
        //clear
        $pool->deleteItem($keys[1].'/');
        $this->assertTrue($pool->hasItem($keys[0]));
        $this->assertFalse($pool->hasItem($keys[1]));
        $this->assertFalse($pool->hasItem($keys[2]));
    }

    public function testDogpile()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('base');
        $item->expiresAfter(3);
        $pool->save($item->set($this->data));

        $item1 = $pool->getItem('base');
        $item1->setPrecomputeTime(3);
        $this->assertFalse($item1->isHit());
        $item2 = $pool->getItem('base');
        $item2->setPrecomputeTime(3);
        $this->assertTrue($item2->isHit());
        
        $item1->expiresAfter(4)
            ->save();

        $item3 = $pool->getItem('base');
        $this->assertTrue($item3->isHit());
        $this->assertAttributeEquals(false, 'locked', $item3);
    }
}