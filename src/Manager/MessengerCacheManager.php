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
    private const ADAPTER_NOT_SUPPORT_TAGS_MESSAGE_TEMPLATE = 'The %s pool does not support tags. Use TagAwareAdapterInterface instead.';

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
        try {
            $cache = (new \ReflectionClass($message))->getAttributes(Cache::class)[0]->newInstance();
        } catch (Throwable) {
            throw new \LogicException(sprintf('The %s class has not declared the %s attribute which is required.', get_class($message), Cache::class));
        }

        if ($cache->pool && !in_array($cache->pool, array_keys($this->pools), true)) {
            throw new \LogicException(sprintf('The %s pool is not configured.', $cache->pool));
        }

        /** @var AdapterInterface */
        $pool = $this->pools[$cache->pool ?? self::DEFAULT_ADAPTER_ALIAS];

        $forceCacheRefresh = false;
        foreach ($stamps as $stamp) {
            if ($stamp instanceof ForceCacheRefreshStamp) {
                $forceCacheRefresh = true;
                break;
            }
        }

        /** @var CacheItem $item */
        $item = $pool->getItem($cacheKey);

        if ($cache->refreshAfter && $item->isHit() && !$forceCacheRefresh) {
            $created = $item->get()->created;

            if ((time() - $created) > $cache->refreshAfter) {
                $this->messageBus->dispatch(
                    new RefreshAsync(
                        $message,
                        $stamps
                    )
                );

                return new Envelope($message, $stamps);
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

            /** @var OwnerIdentifier|DynamicTags $message */
            if ($message instanceof DynamicTags ? $message->getDynamicTags() : $cache->tags) {
                if (!$pool instanceof TagAwareAdapterInterface) {
                    throw new \LogicException(sprintf(self::ADAPTER_NOT_SUPPORT_TAGS_MESSAGE_TEMPLATE, $cache->pool));
                }
                $item->tag(
                    $message instanceof DynamicTags ?
                        $message->getDynamicTags() : ($cache->useOwnerIdentifierForTags ?
                            array_map(fn (string $tag) => $this->tagProvider->createGroupTag($tag, $message->getOwnerIdentifier()), $cache->tags) :
                            $cache->tags
                        )
                );
            }

            if ($cache->group) {
                if (!$pool instanceof TagAwareAdapterInterface) {
                    throw new \LogicException(sprintf(self::ADAPTER_NOT_SUPPORT_TAGS_MESSAGE_TEMPLATE, $cache->pool));
                }
                $item->tag(
                    $this->tagProvider->createGroupTag(
                        $cache->group,
                        $message instanceof OwnerIdentifier
                            ? $message->getOwnerIdentifier()
                            : null
                    )
                );
            } elseif ($message instanceof OwnerIdentifier) {
                if (!$pool instanceof TagAwareAdapterInterface) {
                    throw new \LogicException(sprintf(self::ADAPTER_NOT_SUPPORT_TAGS_MESSAGE_TEMPLATE, $cache->pool));
                }
                $item->tag(
                    $this->tagProvider->createOwnerTag(
                        $message->getOwnerIdentifier()
                    )
                );
            }

            $pool->save($item);
        }

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

        $_tags = [];
        if ($ownerIdentifier && empty($groups)) {
            $_tags[] = $this->tagProvider->createOwnerTag($ownerIdentifier);
        }
        if ($ownerIdentifier && !empty($groups)) {
            foreach ($groups as $group) {
                $_tags[] = $this->tagProvider->createGroupTag($group, $ownerIdentifier);
            }
        }
        if (!empty($tags)) {
            if ($useOwnerIdentifierForTags && $ownerIdentifier) {
                foreach ($tags as $tag) {
                    $_tags[] = $this->tagProvider->createGroupTag($tag, $ownerIdentifier);
                }
            } else {
                $_tags = array_merge($_tags, $tags);
            }
        }

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
}
