<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTtl;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Message\RefreshAsync;
use PBaszak\MessengerCacheBundle\Stamps\CacheRefreshTriggeredStamp;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Throwable;

class MessengerCacheManager implements MessengerCacheManagerInterface
{
    private MessageBusInterface $messageBus;

    /**
     * @param array<string,AdapterInterface> $pools
     */
    public function __construct(
        private array $pools = [],
        string $kernelCacheDir = '',
        private int $refreshTriggeredTtl = 600
    ) {
        if (empty($this->pools)) {
            $this->pools[self::DEFAULT_ADAPTER_ALIAS] = new PhpArrayAdapter(
                $kernelCacheDir.self::DEFAULT_CACHE_FILE,
                new ArrayAdapter(storeSerialized: false)
            );
        }
    }

    public function setMessageBus(MessageBusInterface $messageBus): void
    {
        $this->messageBus = $messageBus;
    }

    public function getPool(string $pool): AdapterInterface
    {
        if (!isset($this->pools[$pool])) {
            throw new \LogicException(sprintf('Pool "%s" is not configured.', $pool));
        }

        return $this->pools[$pool];
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        $cache = $this->extractCacheAttribute($message);
        $pool = $this->getCorrectPool($cache->pool);
        $forceCacheRefresh = $this->isCacheRefreshForced($stamps);
        $refreshTriggeredKey = $cacheKey.'|triggered';
        $resultsStamps = $forceCacheRefresh ? [new ForceCacheRefreshStamp()] : [];

        /** @var CacheItem $item */
        $item = $pool->getItem($cacheKey);

        if ($this->isCacheRefreshable($cache, $item, $forceCacheRefresh)) {
            $triggeredItem = $pool->getItem($refreshTriggeredKey);
            if ($triggeredItem->isHit()) {
                return $item->get()->value->with(...$resultsStamps);
            }

            $triggeredItem->set(true);
            $triggeredItem->expiresAfter($this->refreshTriggeredTtl);
            $pool->save($triggeredItem);
            $resultsStamps[] = new CacheRefreshTriggeredStamp();
            $this->dispatchAsyncCacheRefresh($message, $stamps);

            /*
             * If we have to dispatch cache refresh we did not anything
             * else to do with cache item, so we can return response
             */
            return $item->get()->value->with(...$resultsStamps);
        }

        if ($item->isHit() && !$forceCacheRefresh) {
            return $item->get()->value->with(...$resultsStamps);
        }

        /* item set */
        $item->set(
            (object) [
                'created' => time(),
                'value' => $callback()->withoutAll(ForceCacheRefreshStamp::class),
            ]
        );

        /* item expires */
        $item->expiresAfter($message instanceof DynamicTtl ? $message->getDynamicTtl() : $cache->ttl);

        /* item tags */
        if ($pool instanceof TagAwareAdapterInterface) {
            $tags = $message instanceof DynamicTags ? $message->getDynamicTags() : [];
            $tags = array_merge($tags, $cache->tags);

            foreach ($tags as $tag) {
                $item->tag($tag);
            }
        }

        $pool->save($item);
        $this->delete($refreshTriggeredKey, $cache->pool);

        return $item->get()->value->with(...$resultsStamps);
    }

    public function delete(string $cacheKey, ?string $pool = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        if (empty(array_filter([$pool, $cache, $message]))) {
            throw new \LogicException('At least one argument is required in addition to cacheKey.');
        }

        if ($message && !$cache && !$pool) {
            $cache = (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
            $pool = $cache->pool;
        }

        if ($cache && !$pool) {
            $pool = $cache->pool;
        }

        /** @var AdapterInterface */
        $pool = $this->pools[$pool ?? self::DEFAULT_ADAPTER_ALIAS];

        return $pool->deleteItem($cacheKey);
    }

    public function clear(string $prefix = '', ?string $pool = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        if (empty(array_filter([$pool, $cache, $message]))) {
            throw new \LogicException('At least one argument is required in addition to cacheKey.');
        }

        if ($message && !$cache && !$pool) {
            $cache = (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
            $pool = $cache->pool;
        }

        if ($cache && !$pool) {
            $pool = $cache->pool;
        }

        /** @var AdapterInterface */
        $pool = $this->pools[$pool ?? self::DEFAULT_ADAPTER_ALIAS];

        return $pool->clear($prefix);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When $tags is not valid
     */
    public function invalidate(array $tags = [], ?string $pool = null): array
    {
        if (empty($tags)) {
            throw new \InvalidArgumentException('The tags array cannot be empty.');
        }

        $result = [];
        foreach ($this->pools as $alias => $pool) {
            if ($pool instanceof TagAwareAdapterInterface) {
                foreach ($tags as $tag) {
                    $result[$alias][$tag] = $pool->invalidateTags([$tag]);
                }
            }
        }

        return $result;
    }

    /** @throws \LogicException if Cache attribute is not declared. */
    private function extractCacheAttribute(Cacheable $message): Cache
    {
        try {
            return (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
        } catch (Throwable) {
            throw new \LogicException(sprintf('The %s class has not declared the %s attribute which is required.', get_class($message), Cache::class));
        }
    }

    /** @throws \LogicException if pool is not configured. */
    private function getCorrectPool(?string $pool = null): AdapterInterface
    {
        if ($pool && !in_array($pool, array_keys($this->pools), true)) {
            throw new \LogicException(sprintf('The %s pool is not configured.', $pool));
        }

        /** @var AdapterInterface */
        $pool = $this->pools[$pool ?? self::DEFAULT_ADAPTER_ALIAS];

        return $pool;
    }

    /**
     * @param StampInterface[] $stamps
     */
    private function isCacheRefreshForced(array $stamps): bool
    {
        foreach ($stamps as $stamp) {
            if ($stamp instanceof ForceCacheRefreshStamp) {
                return true;
            }
        }

        return false;
    }

    private function isCacheRefreshable(Cache $cache, CacheItem $item, bool $forceCacheRefresh): bool
    {
        return $item->isHit() && !$forceCacheRefresh && $cache->refreshAfter && (time() - $item->get()->created) > $cache->refreshAfter;
    }

    /**
     * @param StampInterface[] $stamps
     *
     * @throws \LogicException if message bus is not set
     */
    private function dispatchAsyncCacheRefresh(Cacheable $message, array $stamps): void
    {
        if (!isset($this->messageBus)) {
            throw new \LogicException('The message bus is not declared. You have to call $cacheManager->setMessageBus($messageBus) in Your MessageBus decorator to use the async cache refresh.');
        }

        $this->messageBus->dispatch(
            new RefreshAsync(
                $message,
                $stamps
            )
        );
    }
}
