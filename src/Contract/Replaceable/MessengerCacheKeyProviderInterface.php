<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Replaceable;

use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Stamp\StampInterface;

interface MessengerCacheKeyProviderInterface
{
    /** @see https://php.watch/articles/php-hash-benchmark */
    public const HASH_ALGO = 'xxh3';

    /**
     * You should ignore the stamps, but perhaps in your individual case
     * they matter, so they are always available in this method.
     *
     * @param StampInterface[] $stamps
     */
    public function createKey(Cacheable $message, array $stamps = []): string;
}
