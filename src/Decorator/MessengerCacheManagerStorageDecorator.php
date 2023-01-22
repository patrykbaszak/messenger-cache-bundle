<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;

#[AsDecorator(MessengerCacheManagerInterface::class, 10)]
class MessengerCacheManagerStorageDecorator implements MessengerCacheManagerInterface
{
    private static array $storage = [];

    public function __construct(
        private MessengerCacheManagerInterface $decorated,
    ) {}

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        self::$storage[$cacheKey] ??= $this->decorated->get($message, $stamps, $cacheKey, $callback);

        return self::$storage[$cacheKey];
    }

    public function delete(string $cacheKey, ?string $adapter = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        unset(self::$storage[$cacheKey]);

        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }
}
