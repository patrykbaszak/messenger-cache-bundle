<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\Optional\HashableInstance;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Optional\UniqueHash;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheKeyProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

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
                    $message instanceof OwnerIdentifier ? $message->getOwnerIdentifier() : null,
                    hash($this->hashAlgo, get_class($message)),
                    $message instanceof UniqueHash ? $message->getUniqueHash() : hash(
                        $this->hashAlgo,
                        serialize($message instanceof HashableInstance ? $message->getHashableInstance() : $message)
                    ),
                ]
            )
        );
    }
}
