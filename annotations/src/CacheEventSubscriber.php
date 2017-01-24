<?php

namespace kuiper\annotations;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $items = [];

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        return [
            AnnotationEvents::PRE_PARSE => 'beforeParse',
            AnnotationEvents::POST_PARSE => 'afterParse',
        ];
    }

    public function beforeParse($event)
    {
        $this->logger && $this->logger->debug('[AnnotationSubscriber] before parse '.$event->getClassName());
        $item = $this->cache->getItem($key = 'annotations:'.$event->getClassName());
        if ($item->isHit()) {
            $event->setAnnotations($item->get());
        } else {
            $this->items[$key] = $item;
        }
    }

    public function afterParse($event)
    {
        $this->logger && $this->logger->info('[AnnotationSubscriber] after parse '.$event->getClassName());
        $key = 'annotations:'.$event->getClassName();
        if (isset($this->items[$key])) {
            $item = $this->items[$key];
        } else {
            $item = $this->cache->getItem($key);
        }
        $this->cache->save($item->set($event->getAnnotations()));
    }
}
