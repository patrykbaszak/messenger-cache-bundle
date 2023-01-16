<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract;

interface CacheTagProviderInterface
{
    /** @see https://php.watch/articles/php-hash-benchmark */
    public const HASH_ALGO = 'xxh3';
    
    public function createTag(string $group, ?string $groupOwnerId): string;
}
