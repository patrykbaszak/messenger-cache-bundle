<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

interface HashableInstance
{
    /**
     * Returns a `$message` instance, a version that can
     * be hashed to create a cache key. You can use this when the cache is owned by a group of
     * users and at the same time there is a user instance in the object. The hash would be
     * different for each user, so the cache would be individual. It's better if you return
     * an object instance without user, then the cache will be shared within the group.
     */
    public function getHashableInstance(): Cacheable;
}
