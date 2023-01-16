<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract;

interface CacheKeyProviderInterface
{
    /** @see https://php.watch/articles/php-hash-benchmark */
    public const HASH_ALGO = 'xxh3';
    
    /**
     * You should ignore the stamps, but perhaps in your individual case 
     * they matter, so they are always available in this method.
     */
    public function createKey(Cacheable $message, array $stamps = []): string;
}
