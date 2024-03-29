<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Cache
{
    /**
     * @param int|null    $ttl          If `null` the `getDynamicTtl()` method is required
     * @param int|null    $refreshAfter If not `null` then async invalidation action
     *                                  is triggered after return cached data
     * @param string|null $pool         If `null` default pool is used. Do not use classes
     *                                  as argument. Only aliases are expected. Declare them in the config file.
     * @param string[]    $tags         Symfony Cache supports cache tags using. You can declare tags here and use
     *                                  them in invalidation process.
     */
    public function __construct(
        public ?int $ttl = null,
        public ?int $refreshAfter = null,
        public ?string $pool = null,
        public array $tags = [],
    ) {
    }
}
