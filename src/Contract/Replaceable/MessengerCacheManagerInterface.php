<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Replaceable;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

interface MessengerCacheManagerInterface
{
    public const DEFAULT_ADAPTER_ALIAS = 'default';

    public const DEFAULT_CACHE_FILE = '/messenger_cache.php';

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope;

    public function delete(string $cacheKey, ?string $adapter = null, ?Cache $cache = null, ?Cacheable $message = null): bool;
}
