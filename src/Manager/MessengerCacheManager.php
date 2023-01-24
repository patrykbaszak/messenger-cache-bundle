<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTtl;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheOwnerTagProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Throwable;

class MessengerCacheManager implements MessengerCacheManagerInterface
{
    /**
     * @param array<string,AdapterInterface> $adapters
     */
    public function __construct(
        private MessengerCacheOwnerTagProviderInterface $tagProvider,
        private array $adapters = [],
        string $kernelCacheDir = '',
    ) {
        if (empty($this->adapters)) {
            $this->adapters[self::DEFAULT_ADAPTER_ALIAS] = new PhpArrayAdapter(
                $kernelCacheDir.self::DEFAULT_CACHE_FILE,
                new ArrayAdapter(storeSerialized: false)
            );
        }
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        try {
            $cache = (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
        } catch (Throwable) {
            throw new \LogicException(sprintf('The %s class has not declared the %s attribute which is required.', get_class($message), Cache::class));
        }

        /** @var AdapterInterface */
        $adapter = $this->adapters[$cache->adapter ?? self::DEFAULT_ADAPTER_ALIAS];

        $forceCacheRefresh = false;
        foreach ($stamps as $stamp) {
            if ($stamp instanceof ForceCacheRefreshStamp) {
                $forceCacheRefresh = true;
                break;
            }
        }

        /** @var CacheItem $item */
        $item = $adapter->getItem($cacheKey);

        if ($cache->refreshAfter && $item->isHit() && !$forceCacheRefresh) {
            $created = $item->get()->created;

            if ((time() - $created) > $cache->refreshAfter) {
                // todo: trigger async invalidation
            }
        }

        if (!$item->isHit() || $forceCacheRefresh) {
            $item->set(
                (object) [
                    'created' => time(),
                    'value' => $callback(),
                ]
            );

            if ($message instanceof DynamicTtl) {
                $item->expiresAfter($message->getDynamicTtl());
            } else {
                $item->expiresAfter($cache->ttl);
            }

            if ($cache->tags) {
                $item->tag($cache->tags);
            }

            if ($cache->group) {
                $item->tag(
                    $this->tagProvider->createGroupTag(
                        $cache->group,
                        $message instanceof OwnerIdentifier
                            ? $message->getOwnerIdentifier()
                            : null
                    )
                );
            } elseif ($message instanceof OwnerIdentifier) {
                $item->tag(
                    $this->tagProvider->createOwnerTag(
                        $message->getOwnerIdentifier()
                    )
                );
            }

            $adapter->save($item);
        }

        return $item->get()->value;
    }

    public function delete(string $cacheKey, ?string $adapter = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        if (empty(array_filter([$adapter, $cache, $message]))) {
            throw new \LogicException('At least one argument is required in addition to cacheKey.');
        }

        if ($message && !$cache && !$adapter) {
            $cache = (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
            $adapter = $cache->adapter;
        }

        if ($cache && !$adapter) {
            $adapter = $cache->adapter;
        }

        /** @var AdapterInterface */
        $pool = $this->adapters[$adapter ?? self::DEFAULT_ADAPTER_ALIAS];

        return $pool->deleteItem($cacheKey);
    }
}
