<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\CacheManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;

#[AsDecorator(CacheManagerInterface::class, 1)]
class CacheManagerChecksDecorator implements CacheManagerInterface
{
    public function __construct(
        private CacheManagerInterface $decorated,
    ) {
    }

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        return method_exists($message, 'isCacheable') && !$message->isCacheable()
            ? $callback()
            : $this->decorated->get($message, $stamps, $cacheKey, $callback);
    }
}
