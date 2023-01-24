<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Replaceable;

interface MessengerCacheOwnerTagProviderInterface
{
    /** @see https://php.watch/articles/php-hash-benchmark */
    public const HASH_ALGO = 'xxh3';

    public function createGroupTag(string $group, ?string $groupId): string;

    public function createOwnerTag(string $ownerId): string;
}
