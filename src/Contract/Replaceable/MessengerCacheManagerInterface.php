<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Replaceable;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

interface MessengerCacheManagerInterface
{
    public const DEFAULT_ADAPTER_ALIAS = 'default';

    public const DEFAULT_CACHE_FILE = '/messenger_cache.php';

    public function setMessageBus(MessageBusInterface $messageBus): void;

    public function getPool(string $pool): AdapterInterface;

    /**
     * @param StampInterface[] $stamps
     */
    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope;

    public function delete(string $cacheKey, ?string $pool = null, ?Cache $cache = null, ?Cacheable $message = null): bool;

    public function clear(string $prefix = '', ?string $pool = null, ?Cache $cache = null, ?Cacheable $message = null): bool;

    /**
     * @param string[]    $tags
     * @param string[]    $groups
     * @param string|null $pool   if `null` the all available TagAware pools will be invalidated
     *
     * @return array<string,array<string,bool>> The invalidated tags. ['pool_alias' => ['tag' => true]]
     */
    public function invalidate(array $tags = [], array $groups = [], ?string $ownerIdentifier = null, bool $useOwnerIdentifierForTags = false, ?string $pool = null): array;
}
