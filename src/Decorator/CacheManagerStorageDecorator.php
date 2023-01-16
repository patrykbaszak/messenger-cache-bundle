<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\CacheManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;

#[AsDecorator(CacheManagerInterface::class, 10)]
class CacheManagerStorageDecorator implements CacheManagerInterface
{
    private static array $storage = [];

    public function __construct(
        private CacheManagerInterface $decorated,
    ) {}

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        self::$storage[$cacheKey] ??= $this->decorated->get($message, $stamps, $cacheKey, $callback);

        return self::$storage[$cacheKey];
    }
}
