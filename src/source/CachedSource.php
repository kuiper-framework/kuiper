<?php
namespace kuiper\di\source;

use Psr\Cache\CacheItemPoolInterface;
use Serializable;

class CachedSource implements SourceInterface
{
    /**
     * @var SourceInterface
     */
    private $source;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * Entries that cannot store in cache
     *
     * @var array
     */
    private $negativeCache;
    
    public function __construct(SourceInterface $source, CacheItemPoolInterface $pool)
    {
        $this->source = $source;
        $this->cache = $pool;
    }

    /**
     * @inheritDoc
     */
    public function has($name)
    {
        return $this->source->has($name);
    }

    /**
     * @inheritDoc
     */
    public function get($name)
    {
        if (!empty($this->negativeCache[$name])) {
            return $this->source->get($name);
        }
        $item = $this->cache->getItem($cacheKey = 'di\definition:' . $name);
        if (!$item->isHit()) {
            $entry = $this->source->get($name);
            if ($entry !== null) {
                if ($entry->getDefinition() instanceof Serializable) {
                    $this->cache->save($item->set($entry));
                } else {
                    $this->negativeCache[$name] = true;
                    return $entry;
                }
            }
        }
        return $item->get();
    }
}
