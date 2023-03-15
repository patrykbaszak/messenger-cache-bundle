<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTtl;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheOwnerTagProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Message\RefreshAsync;
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
        private MessengerCacheOwnerTagProviderInterface $tagProvider,
        private array $pools = [],
        string $kernelCacheDir = '',
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

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        $cache = $this->extractCacheAttribute($message);
        $pool = $this->getCorrectPool($cache->pool);
        $forceCacheRefresh = $this->isCacheRefreshForced($stamps);

        /** @var CacheItem $item */
        $item = $pool->getItem($cacheKey);

        if ($this->isCacheRefreshable($cache, $item, $forceCacheRefresh)) {
            $this->dispatchAsyncCacheRefresh($message, $stamps);

            /*
             * If we have to dispatch cache refresh we did not anything
             * else to do with cache item, so we can return response
             */
            return $item->get()->value;
        }

        if ($item->isHit() && !$forceCacheRefresh) {
            return $item->get()->value;
        }

        /* item set */
        $item->set(
            (object) [
                'created' => time(),
                'value' => $callback(),
            ]
        );

        /* item expires */
        $item->expiresAfter($message instanceof DynamicTtl ? $message->getDynamicTtl() : $cache->ttl);

        /* item tags */
        if ($pool instanceof TagAwareAdapterInterface) {
            $tags = $this->createTags(
                $message instanceof OwnerIdentifier ? $message->getOwnerIdentifier() : null,
                $message instanceof DynamicTags ? $message->getDynamicTags() : $cache->tags,
                $cache->useOwnerIdentifierForTags,
                $cache->group
            );

            foreach ($tags as $tag) {
                $item->tag($tag);
            }
        }

        $pool->save($item);

        return $item->get()->value;
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
    public function invalidate(array $tags = [], array $groups = [], ?string $ownerIdentifier = null, bool $useOwnerIdentifierForTags = false, ?string $pool = null): array
    {
        if (empty($tags) && empty($groups) && empty($ownerIdentifier)) {
            throw new \LogicException('At least one argument (tags, groups or ownerIdentifier) is required.');
        }
        $_tags = $this->createTags($ownerIdentifier, $tags, $useOwnerIdentifierForTags, ...$groups);

        $result = [];
        foreach ($this->pools as $alias => $pool) {
            if ($pool instanceof TagAwareAdapterInterface) {
                foreach ($_tags as $_tag) {
                    $result[$alias][$_tag] = $pool->invalidateTags([$_tag]);
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

    /**
     * @param string[] $tags
     *
     * @return string[]
     */
    private function createTags(?string $ownerIdentifier, array $tags, bool $useOwnerIdentifierForTags, ?string ...$groups): array
    {
        $groups = array_filter($groups);
        $createdTags = [];
        if ($ownerIdentifier) {
            $createdTags[] = $this->tagProvider->createOwnerTag($ownerIdentifier);
        }
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $createdTags[] = $this->tagProvider->createGroupTag($group, $ownerIdentifier);
            }
        }
        if (!empty($tags)) {
            if ($useOwnerIdentifierForTags && $ownerIdentifier) {
                foreach ($tags as $tag) {
                    $createdTags[] = $this->tagProvider->createGroupTag($tag, $ownerIdentifier);
                }
            } else {
                $createdTags = array_merge($createdTags, $tags);
            }
        }

        return array_unique($createdTags);
    }
}
