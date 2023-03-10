<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\CacheableCallback;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

class MessengerCacheManagerChecksDecorator implements MessengerCacheManagerInterface
{
    public function __construct(
        private MessengerCacheManagerInterface $decorated,
    ) {
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        return $message instanceof CacheableCallback && !$message->isCacheable()
            ? $callback()
            : $this->decorated->get($message, $stamps, $cacheKey, $callback);
    }

    public function delete(string $cacheKey, ?string $adapter = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $tags = [], array $groups = [], ?string $ownerIdentifier = null, bool $useOwnerIdentifierForTags = false, ?string $adapter = null): array
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }
}
