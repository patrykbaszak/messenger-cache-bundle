<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface CacheableCallback
{
    /**
     * Returns a dynamically generated `true`/`false` value and decides
     * whether the request will be served using the cache.
     */
    public function isCacheable(): bool;
}
