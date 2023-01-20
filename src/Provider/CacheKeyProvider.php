<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\MessengerCacheKeyProviderInterface;

class CacheKeyProvider implements MessengerCacheKeyProviderInterface
{
    public function __construct(
        private string $hashAlgo = self::HASH_ALGO
    ) {
    }

    public function createKey(Cacheable $message, array $stamps = []): string
    {
        return implode(
            '|',
            array_filter(
                [
                    method_exists($message, 'getOwnerIdentifier') ? $message->getOwnerIdentifier() : null,
                    hash($this->hashAlgo, get_class($message)),
                    method_exists($message, 'getUniqueHash') ? $message->getUniqueHash() : hash(
                        $this->hashAlgo,
                        serialize(method_exists($message, 'getHashableInstance') ? $message->getHashableInstance() : $message)
                    )
                ]
            )
        );
    }
}
