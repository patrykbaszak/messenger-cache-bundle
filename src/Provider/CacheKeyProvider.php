<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\CacheKeyProviderInterface;

class CacheKeyProvider implements CacheKeyProviderInterface
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
                    hash(
                        $this->hashAlgo,
                        serialize(method_exists($message, 'getHashableInstance') ? $message->getHashableInstance() : $message)
                    )
                ]
            )
        );
    }
}
