<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

class MessengerCacheManagerStorageDecorator implements MessengerCacheManagerInterface
{
    /** @var array<string, Envelope> */
    private static array $storage = [];

    /** @todo Add tags support */
    // /** @var array<string,string> */
    // private static array $tags = [];

    public function __construct(
        private MessengerCacheManagerInterface $decorated,
    ) {
    }

    public function setMessageBus(object $messageBus): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @param StampInterface[] $stamps
     */
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

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $tags = [], array $groups = [], ?string $ownerIdentifier = null, bool $useOwnerIdentifierForTags = false, ?string $adapter = null): array
    {
        /* @todo Add tags support */
        self::$storage = [];

        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }
}
