<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\CacheKeyProviderInterface;

class CacheKeyProvider implements CacheKeyProviderInterface
{
    /** @see https://php.watch/articles/php-hash-benchmark */
    public const HASH_ALGO = 'xxh3';

    public function __construct(
        private string $hashAlgo = self::HASH_ALGO
    ) {}

    public function createKey(object $message, array $stamps = []): string
    {
        return hash($this->hashAlgo, serialize($message));
    }
}
