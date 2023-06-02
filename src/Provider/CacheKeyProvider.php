<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicKeyPart;
use PBaszak\MessengerCacheBundle\Contract\Optional\HashableInstance;
use PBaszak\MessengerCacheBundle\Contract\Optional\UniqueHash;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheKeyProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Stamp\StampInterface;

class CacheKeyProvider implements MessengerCacheKeyProviderInterface
{
    public function __construct(
        private string $hashAlgo = self::HASH_ALGO
    ) {
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function createKey(Cacheable $message, array $stamps = []): string
    {
        if (in_array($this->hashAlgo, hash_algos(), true)) {
            return implode(
                '|',
                array_filter(
                    [
                        hash($this->hashAlgo, get_class($message)),
                        $message instanceof DynamicKeyPart ? $message->getDynamicKeyPart() : null,
                        $message instanceof UniqueHash ? $message->getUniqueHash() : hash(
                            $this->hashAlgo,
                            serialize($message instanceof HashableInstance ? $message->getHashableInstance() : $message)
                        ),
                    ]
                )
            );
        }

        /*
         * PHP 8.0 do not support `xxh3` hash algorithm.
         * @see https://php.watch/versions/8.1/xxHash
         */
        return implode(
            '|',
            array_filter(
                [
                    (string) crc32(get_class($message)),
                    $message instanceof DynamicKeyPart ? $message->getDynamicKeyPart() : null,
                    $message instanceof UniqueHash ? $message->getUniqueHash() : (string) crc32(
                        serialize($message instanceof HashableInstance ? $message->getHashableInstance() : $message)
                    ),
                ]
            )
        );
    }
}
