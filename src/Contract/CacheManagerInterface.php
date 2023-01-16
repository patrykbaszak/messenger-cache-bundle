<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract;

use Symfony\Component\Messenger\Envelope;

interface CacheManagerInterface
{
    public const DEFAULT_ADAPTER_ALIAS = 'default';

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope;
}
