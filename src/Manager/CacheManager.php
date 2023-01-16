<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\CacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\CacheTagProviderInterface;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Messenger\Envelope;

class CacheManager implements CacheManagerInterface
{
    /**
     * @param array<string,AdapterInterface>
     */
    public function __construct(
        private CacheTagProviderInterface $tagProvider,
        private array $adapters = [],
    ) {
    }

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        $cache = (new ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
        /** @var AdapterInterface */
        $adapter = $this->adapters[$cache->adapter ?? self::DEFAULT_ADAPTER_ALIAS];

        $forceCacheRefresh = false;
        foreach ($stamps as $stamp) {
            if ($stamp instanceof ForceCacheRefreshStamp) {
                $forceCacheRefresh = true; break;
            }
        }

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
                    'value' => $callback()
                ]
            );
            $item->expiresAfter($cache->ttl ?? $message->getDynamicTtl());
            $item->tag($cache->tags);

            if ($cache->group) {
                $item->tag(
                    $this->tagProvider->createTag(
                        $cache->group,
                        method_exists($message, 'getOwnerIdentifier')
                            ? $message->getOwnerIdentifier()
                            : null
                    )
                );
            }

            $adapter->save($item);
        }

        return $item->get()->value;
    }
}