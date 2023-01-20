<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\MessengerCacheManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;

#[AsDecorator(MessengerCacheManagerInterface::class, 1)]
class MessengerCacheManagerChecksDecorator implements MessengerCacheManagerInterface
{
    public function __construct(
        private MessengerCacheManagerInterface $decorated,
    ) {
    }

    public function get(Cacheable $message, array $stamps, string $cacheKey, callable $callback): Envelope
    {
        return method_exists($message, 'isCacheable') && !$message->isCacheable()
            ? $callback()
            : $this->decorated->get($message, $stamps, $cacheKey, $callback);
    }

    public function delete(string $cacheKey, ?string $adapter = null, ?Cache $cache = null, ?Cacheable $message = null): bool
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }
}
