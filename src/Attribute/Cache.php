<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

use Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Cache
{
    /**
     * @param int|null    $ttl          if `null` the `getDynamicTtl()` method is required
     * @param int|null    $refreshAfter if not `null` then async invalidation action
     *                                  is triggered after return cached data
     * @param string|null $adapter      If `null` default adapter is used. Do not use classes
     *                                  as argument. Only aliases are expected. Declare them in the config file.
     * @param string|null $group        Define group of messages which You want to invalidate
     *                                  together. Works with `getOwnerIdentifier()` method if declared.
     * @param string[]    $tags         Symfony Cache supports cache tags using. You can declare tags here.
     */
    public function __construct(
        public readonly ?int $ttl = 3600,
        public readonly ?int $refreshAfter = null,
        public readonly ?string $adapter = null,
        public readonly ?string $group = null,
        public readonly array $tags = [],
    ) {
    }
}
