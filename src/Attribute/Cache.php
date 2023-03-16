<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

use Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Cache
{
    /**
     * @param int|null    $ttl                       If `null` the `getDynamicTtl()` method is required
     * @param int|null    $refreshAfter              If not `null` then async invalidation action
     *                                               is triggered after return cached data
     * @param string|null $pool                      If `null` default pool is used. Do not use classes
     *                                               as argument. Only aliases are expected. Declare them in the config file.
     * @param string|null $group                     Define group of messages which You want to invalidate
     *                                               together. Works with `getOwnerIdentifier()` method if declared.
     *                                               example: `productType` and `getOwnerIdentifier()` returns `user_123`. If
     *                                               You invalidate `productType` group and You add OwnerIdentifier `user_123`
     *                                               then all messages grouped as `productType` and owned by `user_123`
     *                                               will be invalidated.
     * @param string[]    $tags                      Symfony Cache supports cache tags using. You can declare tags here and use
     *                                               them in invalidation process.
     * @param bool        $useOwnerIdentifierForTags if `true` then `getOwnerIdentifier()` method is required and all tags will be
     *                                               prefixed with owner identifier
     */
    public function __construct(
        public readonly ?int $ttl = null,
        public readonly ?int $refreshAfter = null,
        public readonly ?string $pool = null,
        public readonly ?string $group = null,
        public readonly array $tags = [],
        public readonly bool $useOwnerIdentifierForTags = false,
    ) {
    }
}
